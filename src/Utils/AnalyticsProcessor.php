<?php

namespace Flagsmith\Utils;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * AnalyticsProcessor is used to track how often individual Flags are evaluated within
    the Flagsmith SDK. Docs: https://docs.flagsmith.com/advanced-use/flag-analytics.
 */
class AnalyticsProcessor
{
    public const ANALYTICS_ENDPOINT = '/analytics/flags/';
    public const ANALYTICS_TIMER = 10;

    private string $analytics_endpoint;
    private string $environment_key;
    private int $last_flushed;
    public array $analytics_data = [];
    private int $timeout;
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;

    public function __construct(
        string $environmentKey,
        string $baseApiUrl,
        int $timeout = 5,
        ClientInterface $client = null,
        RequestFactoryInterface $requestFactory = null,
        StreamFactoryInterface $streamFactory = null
    ) {
        $this->analytics_endpoint = rtrim($baseApiUrl, '/') . self::ANALYTICS_ENDPOINT;
        $this->environment_key = $environmentKey;
        $this->last_flushed = time();
        $this->analytics_data = [];
        $this->timeout = $timeout;

        //We default to using Guzzle for the HTTP client (as this is how it worked in 1.0)
        $this->client = $client ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
    }

    public function __destruct()
    {
        $this->flush();
    }

    public function flush()
    {
        if (empty($this->analytics_data)) {
            return;
        }

        $stream = $this->streamFactory->createStream(
            json_encode($this->analytics_data)
        );

        $request = $this->requestFactory
            ->createRequest('POST', $this->analytics_endpoint)
            ->withHeader('Accept', 'application/json')
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('X-Environment-Key', $this->environment_key)
            ->withBody($stream);

        try {
            $this->client->sendRequest($request);
        } catch (\Exception $e) {
        }
        // suppress all warnings and errors

        $this->analytics_data = [];
        $this->last_flushed = time();
    }

    public function trackFeature(string $featureName)
    {
        $this->analytics_data[$featureName] = ($this->analytics_data[$featureName]  ?? 0) + 1;
        if ((time() - $this->last_flushed) > self::ANALYTICS_TIMER) {
            $this->flush();
        }
    }
}
