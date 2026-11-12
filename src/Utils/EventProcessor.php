<?php

namespace Flagsmith\Utils;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * EventProcessor buffers tracking and exposure events, and posts them to the Flagsmith events API.
 *
 * PHP has no background timer, so buffered events are sent when the processor is destroyed (e.g. at
 * the end of a request), when `flush()` or `close()` is called, or when an event is tracked after the
 * flush interval has elapsed. The buffer holds at most `$maxBuffer` events: a full buffer drops its
 * oldest event.
 *
 * Recording an event never fails the calling application. A flush sends the buffered events in one
 * request, retrying transient failures (a connection error, a timeout, 408, 429 or a 5xx) up to
 * `MAX_ATTEMPTS` times, backing off from 1 second, doubling, with full jitter. A batch whose last
 * attempt failed transiently is kept, ahead of newer events, for the next flush, unless that attempt
 * got a 500. A batch the events API rejects otherwise is dropped, and a 401 or 403 stops event
 * tracking altogether.
 *
 * Each request times out after `REQUEST_TIMEOUT_SECONDS` with a Guzzle client, such as the one
 * discovered by default. PSR-18 has no per-request timeout, so with another client a request waits as
 * long as that client allows: configure it with a short timeout to bound flushing.
 */
class EventProcessor
{
    /** The only `$`-prefixed event name an SDK is allowed to send. */
    public const FLAG_EXPOSURE_EVENT = '$flag_exposure';
    public const DEFAULT_EVENTS_API_URL = 'https://events.api.flagsmith.com/';
    public const EVENTS_ENDPOINT = 'v1/events';
    public const DEFAULT_MAX_BUFFER = 1000;
    public const DEFAULT_FLUSH_INTERVAL_SECONDS = 10;
    public const MAX_ATTEMPTS = 3;
    public const REQUEST_TIMEOUT_SECONDS = 3;
    public const RETRY_BACKOFF_SECONDS = 1;
    private const RETRIED_STATUSES = [408, 429];
    private const STOPPING_STATUSES = [401, 403];
    /** A batch whose last attempt got this status is dropped rather than kept, not to send it forever. */
    private const DROPPING_SERVER_ERROR = 500;

    private string $eventsEndpoint;
    private string $environmentKey;
    private int $maxBuffer;
    private int $flushIntervalSeconds;
    private int $lastFlushed;
    /** @var array<int, array<string, mixed>> */
    public array $events = [];
    /** @var array<string, true> */
    private array $bufferedExposures = [];
    /** Whether the events API refused the environment key, or the processor was closed. */
    private bool $stopped = false;
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;
    private \Closure $sleep;

    /**
     * @param \Closure|null $sleep waits the given number of seconds (a float) before a retry
     */
    public function __construct(
        string $environmentKey,
        ?string $eventsApiUrl = null,
        int $maxBuffer = self::DEFAULT_MAX_BUFFER,
        int $flushIntervalSeconds = self::DEFAULT_FLUSH_INTERVAL_SECONDS,
        ?ClientInterface $client = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        ?\Closure $sleep = null
    ) {
        $this->eventsEndpoint = rtrim($eventsApiUrl ?? self::DEFAULT_EVENTS_API_URL, '/') . '/' . self::EVENTS_ENDPOINT;
        $this->environmentKey = $environmentKey;
        $this->maxBuffer = $maxBuffer;
        $this->flushIntervalSeconds = $flushIntervalSeconds;
        $this->lastFlushed = time();

        $this->client = $client ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
        $this->sleep = $sleep ?? static fn (float $seconds) => usleep((int) round($seconds * 1000000));
    }

    public function __destruct()
    {
        $this->flush();
    }

    /**
     * Buffer a custom event.
     * @param string $event
     * @param string|null $identifier
     * @param string|int|float|bool|null $value
     * @param array<string, mixed>|null $traits
     * @param array<string, mixed>|null $metadata
     * @return void
     */
    public function trackEvent(
        string $event,
        ?string $identifier = null,
        string|int|float|bool|null $value = null,
        ?array $traits = null,
        ?array $metadata = null
    ): void {
        $this->bufferEvent($event, null, $identifier, $value, $traits, $metadata);
    }

    /**
     * Buffer a `$flag_exposure` event. An exposure equal to one already buffered is discarded.
     * @param string $featureName
     * @param string $identifier
     * @param string|int|float|bool|null $value
     * @param array<string, mixed>|null $traits
     * @param array<string, mixed>|null $metadata
     * @return void
     */
    public function trackExposureEvent(
        string $featureName,
        string $identifier,
        string|int|float|bool|null $value = null,
        ?array $traits = null,
        ?array $metadata = null
    ): void {
        $this->bufferEvent(self::FLAG_EXPOSURE_EVENT, $featureName, $identifier, $value, $traits, $metadata);
    }

    /**
     * Post all buffered events to the Flagsmith events API, retrying transient failures.
     * @return void
     */
    public function flush(): void
    {
        $this->send(null);
    }

    /**
     * Post all buffered events, giving up once `$timeoutSeconds` have elapsed, and stop tracking events.
     * @param float $timeoutSeconds
     * @return void
     */
    public function close(float $timeoutSeconds): void
    {
        $this->send(microtime(true) + $timeoutSeconds);
        $this->stop();
    }

    /**
     * @param float|null $deadline the time to give up by, from `microtime(true)`, or null for none
     * @return void
     */
    private function send(?float $deadline): void
    {
        $this->lastFlushed = time();
        if ($this->stopped || empty($this->events)) {
            return;
        }

        try {
            $body = $this->streamFactory->createStream(json_encode(['events' => $this->events], JSON_THROW_ON_ERROR));
        } catch (\Exception $e) {
            // Recording events must never fail the application: the batch is dropped.
            $this->clear();
            return;
        }
        $request = $this->requestFactory
            ->createRequest('POST', $this->eventsEndpoint)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('X-Environment-Key', $this->environmentKey)
            ->withHeader('Flagsmith-SDK-User-Agent', UserAgent::get())
            ->withHeader('User-Agent', UserAgent::get())
            ->withBody($body);

        $status = null;
        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            if ($attempt > 1) {
                // Full jitter: any wait up to the backoff
                $backoff = self::RETRY_BACKOFF_SECONDS * 2 ** ($attempt - 2);
                $wait = $backoff * mt_rand() / mt_getrandmax();
                if (!is_null($deadline) && microtime(true) + $wait >= $deadline) {
                    break;
                }
                ($this->sleep)($wait);
            }
            $timeout = self::REQUEST_TIMEOUT_SECONDS;
            if (!is_null($deadline)) {
                $timeout = min($timeout, $deadline - microtime(true));
                if ($timeout <= 0) {
                    break;
                }
            }

            $body->rewind();
            $status = $this->post($request, $timeout);
            if ($status !== null && $status < 300) {
                $this->clear();
                return;
            }
            if (in_array($status, self::STOPPING_STATUSES, true)) {
                $this->stop();
                return;
            }
            if (!self::isTransient($status)) {
                $this->clear();
                return;
            }
        }

        if ($status === self::DROPPING_SERVER_ERROR) {
            $this->clear();
        }
        // Otherwise the batch stays buffered, ahead of newer events, for the next flush.
    }

    /**
     * Send the request, returning its response's status, or null if it got none.
     * @param RequestInterface $request
     * @param float $timeoutSeconds
     * @return int|null
     */
    private function post(RequestInterface $request, float $timeoutSeconds): ?int
    {
        try {
            if ($this->client instanceof \GuzzleHttp\ClientInterface) {
                $response = $this->client->send($request, ['timeout' => $timeoutSeconds, 'http_errors' => false]);
            } else {
                $response = $this->client->sendRequest($request);
            }
            return $response->getStatusCode();
        } catch (\Exception $e) {
            return null;
        }
    }

    private static function isTransient(?int $status): bool
    {
        return $status === null || $status >= 500 || in_array($status, self::RETRIED_STATUSES, true);
    }

    private function clear(): void
    {
        $this->events = [];
        $this->bufferedExposures = [];
    }

    private function stop(): void
    {
        $this->clear();
        $this->stopped = true;
    }

    private function bufferEvent(
        string $event,
        ?string $featureName,
        ?string $identifier,
        string|int|float|bool|null $value,
        ?array $traits,
        ?array $metadata
    ): void {
        if ($this->stopped) {
            return;
        }

        $value = is_null($value) || is_string($value) ? $value : json_encode($value);
        $metadata = array_merge($metadata ?? [], ['sdk_version' => UserAgent::getVersion()]);
        $bufferedEvent = [
            'event' => $event,
            'feature_name' => $featureName,
            'identifier' => $identifier,
            'value' => $value,
            'traits' => empty($traits) ? null : $traits,
            'metadata' => $metadata,
            'timestamp' => (int) floor(microtime(true) * 1000),
        ];

        $exposure = self::exposureKey($bufferedEvent);
        if (!is_null($exposure)) {
            if (isset($this->bufferedExposures[$exposure])) {
                return;
            }
            $this->bufferedExposures[$exposure] = true;
        }

        $this->events[] = $bufferedEvent;
        while (count($this->events) > $this->maxBuffer) {
            $dropped = array_shift($this->events);
            unset($this->bufferedExposures[self::exposureKey($dropped) ?? '']);
        }

        if ((time() - $this->lastFlushed) >= $this->flushIntervalSeconds) {
            $this->flush();
        }
    }

    /**
     * What makes an exposure the same as another, or null for an event other than an exposure.
     * @param array<string, mixed> $event
     * @return string|null
     */
    private static function exposureKey(array $event): ?string
    {
        if ($event['event'] !== self::FLAG_EXPOSURE_EVENT) {
            return null;
        }

        return json_encode([$event['feature_name'], $event['identifier'], $event['value'], $event['metadata']['experiment_id'] ?? null]);
    }
}
