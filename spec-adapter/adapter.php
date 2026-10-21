<?php

/**
 * Adapter that lets the Flagsmith SDK behavioural spec drive this SDK.
 *
 * Protocol: https://github.com/Flagsmith/sdk-spec/blob/main/ADAPTER.md
 *
 * SDK clients must outlive a single request, so this is a long-running, single-threaded HTTP
 * server built on `stream_socket_server` rather than `php -S`. The runner sends one command at a
 * time, so handling connections sequentially is enough.
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Composer\InstalledVersions;
use Flagsmith\Flagsmith;
use Flagsmith\Models\Flags;

const SDK_NAME = 'flagsmith/flagsmith-php-client';

/** @var array<string, Flagsmith> $clients */
$clients = [];
$nextClientId = 1;

function serialiseFlags(Flags $flags): array
{
    $serialised = [];
    foreach ($flags->allFlags() as $flag) {
        $serialised[] = [
            'featureName' => $flag->getFeatureName(),
            'enabled' => $flag->getEnabled(),
            'value' => $flag->getValue(),
        ];
    }

    return ['flags' => $serialised];
}

/**
 * @return array{0: int, 1: mixed}
 */
function route(string $method, string $path, string $body): array
{
    global $clients, $nextClientId;

    if ($method === 'GET' && $path === '/health') {
        return [200, ['sdk' => 'flagsmith-php-client', 'version' => InstalledVersions::getPrettyVersion(SDK_NAME)]];
    }

    if ($method === 'POST' && $path === '/clients') {
        $request = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        $clientId = (string) $nextClientId++;
        $clients[$clientId] = new Flagsmith(apiKey: $request->environmentKey, host: $request->apiUrl);

        return [201, ['clientId' => $clientId]];
    }

    $notFound = [404, ['error' => "No route for {$method} {$path}"]];
    if (!preg_match('#^/clients/(?<clientId>[^/]+)(?<action>/[a-z-]+)?$#', $path, $match)) {
        return $notFound;
    }
    $clientId = $match['clientId'];
    $action = $match['action'] ?? '';
    if (!isset($clients[$clientId])) {
        return $notFound;
    }

    if ($method === 'DELETE' && $action === '') {
        unset($clients[$clientId]);

        return [204, null];
    }

    if ($method === 'POST' && $action === '/environment-flags') {
        return [200, serialiseFlags($clients[$clientId]->getEnvironmentFlags())];
    }

    if ($method === 'POST' && $action === '/identity-flags') {
        // Decode JSON objects as stdClass, which is what the SDK expects for traits.
        $request = json_decode($body, false, 512, JSON_THROW_ON_ERROR);
        $flags = $clients[$clientId]->getIdentityFlags($request->identifier, $request->traits ?? null);

        return [200, serialiseFlags($flags)];
    }

    return $notFound;
}

/**
 * @return array{0: string, 1: string, 2: string}|null Method, path and body.
 */
function readRequest($connection): ?array
{
    $requestLine = fgets($connection);
    if ($requestLine === false) {
        return null;
    }
    [$method, $path] = explode(' ', trim($requestLine));

    $contentLength = 0;
    while (($line = fgets($connection)) !== false && trim($line) !== '') {
        [$name, $value] = array_map('trim', explode(':', $line, 2)) + [1 => ''];
        if (strcasecmp($name, 'Content-Length') === 0) {
            $contentLength = (int) $value;
        }
    }

    $body = '';
    while (strlen($body) < $contentLength && !feof($connection)) {
        $body .= fread($connection, $contentLength - strlen($body));
    }

    return [$method, parse_url($path, PHP_URL_PATH), $body];
}

function sendResponse($connection, int $status, mixed $body): void
{
    $reasons = [200 => 'OK', 201 => 'Created', 204 => 'No Content', 404 => 'Not Found', 500 => 'Internal Server Error'];
    // Keep floats such as 1.0 as JSON numbers with a fractional part.
    $payload = $body === null ? '' : json_encode($body, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    fwrite(
        $connection,
        "HTTP/1.1 {$status} {$reasons[$status]}\r\n"
        . "Content-Type: application/json\r\n"
        . 'Content-Length: ' . strlen($payload) . "\r\n"
        . "Connection: close\r\n\r\n"
        . $payload
    );
}

$port = getenv('PORT') ?: '8080';
$server = stream_socket_server("tcp://0.0.0.0:{$port}", $errno, $errstr);
if ($server === false) {
    fwrite(STDERR, "adapter: cannot listen on :{$port}: {$errstr}\n");
    exit(1);
}
echo "adapter: listening on :{$port}\n";

while (true) {
    $connection = @stream_socket_accept($server, -1);
    if ($connection === false) {
        continue;
    }

    try {
        $request = readRequest($connection);
        if ($request === null) {
            continue;
        }
        [$method, $path, $body] = $request;
        try {
            [$status, $response] = route($method, $path, $body);
        } catch (Throwable $e) {
            // Report SDK errors to the runner.
            fwrite(STDERR, "adapter: {$e}\n");
            [$status, $response] = [500, ['error' => get_class($e) . ': ' . $e->getMessage()]];
        }
        sendResponse($connection, $status, $response);
        echo "adapter: {$method} {$path} {$status}\n";
    } finally {
        fclose($connection);
    }
}
