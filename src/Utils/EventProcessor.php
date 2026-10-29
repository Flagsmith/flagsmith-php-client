<?php

namespace Flagsmith\Utils;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * EventProcessor buffers tracking and exposure events, and posts them to the Flagsmith events API.
 *
 * PHP has no background timer, so buffered events are sent when the processor is destroyed (e.g. at
 * the end of a request), when `flush()` is called, when the buffer is full, or when an event is
 * tracked after the flush interval has elapsed.
 *
 * Recording an event never fails the calling application: a batch that cannot be posted is dropped.
 * Flushing sends the request synchronously, and PSR-18 has no per-request timeout, so a flush waits
 * as long as the HTTP client allows. Pass a `$client` configured with a short timeout to bound it.
 */
class EventProcessor
{
    /** The only `$`-prefixed event name an SDK is allowed to send. */
    public const FLAG_EXPOSURE_EVENT = '$flag_exposure';
    public const DEFAULT_EVENTS_API_URL = 'https://events.api.flagsmith.com/';
    public const EVENTS_ENDPOINT = 'v1/events';
    public const DEFAULT_MAX_BUFFER = 1000;
    public const DEFAULT_FLUSH_INTERVAL_SECONDS = 10;

    private string $eventsEndpoint;
    private string $environmentKey;
    private int $maxBuffer;
    private int $flushIntervalSeconds;
    private int $lastFlushed;
    /** @var array<int, array<string, mixed>> */
    public array $events = [];
    /** @var array<string, true> */
    private array $bufferedExposures = [];
    private ClientInterface $client;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;

    public function __construct(
        string $environmentKey,
        ?string $eventsApiUrl = null,
        int $maxBuffer = self::DEFAULT_MAX_BUFFER,
        int $flushIntervalSeconds = self::DEFAULT_FLUSH_INTERVAL_SECONDS,
        ?ClientInterface $client = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null
    ) {
        $this->eventsEndpoint = rtrim($eventsApiUrl ?? self::DEFAULT_EVENTS_API_URL, '/') . '/' . self::EVENTS_ENDPOINT;
        $this->environmentKey = $environmentKey;
        $this->maxBuffer = $maxBuffer;
        $this->flushIntervalSeconds = $flushIntervalSeconds;
        $this->lastFlushed = time();

        $this->client = $client ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
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
     * Post all buffered events to the Flagsmith events API.
     * @return void
     */
    public function flush(): void
    {
        $this->lastFlushed = time();
        if (empty($this->events)) {
            return;
        }

        $events = $this->events;
        $this->events = [];
        $this->bufferedExposures = [];

        $request = $this->requestFactory
            ->createRequest('POST', $this->eventsEndpoint)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('X-Environment-Key', $this->environmentKey)
            ->withHeader('Flagsmith-SDK-User-Agent', UserAgent::get())
            ->withHeader('User-Agent', UserAgent::get());

        try {
            $request = $request->withBody(
                $this->streamFactory->createStream(json_encode(['events' => $events], JSON_THROW_ON_ERROR))
            );
            $this->client->sendRequest($request);
        } catch (\Exception $e) {
            // Recording events must never fail the application: the batch is dropped.
        }
    }

    private function bufferEvent(
        string $event,
        ?string $featureName,
        ?string $identifier,
        string|int|float|bool|null $value,
        ?array $traits,
        ?array $metadata
    ): void {
        $value = is_null($value) || is_string($value) ? $value : json_encode($value);
        $metadata = array_merge($metadata ?? [], ['sdk_version' => UserAgent::getVersion()]);

        if ($event === self::FLAG_EXPOSURE_EVENT) {
            $exposure = json_encode([$featureName, $identifier, $value, $metadata['experiment_id'] ?? null]);
            if (isset($this->bufferedExposures[$exposure])) {
                return;
            }
            $this->bufferedExposures[$exposure] = true;
        }

        $this->events[] = [
            'event' => $event,
            'feature_name' => $featureName,
            'identifier' => $identifier,
            'value' => $value,
            'traits' => empty($traits) ? null : $traits,
            'metadata' => $metadata,
            'timestamp' => (int) floor(microtime(true) * 1000),
        ];

        if (count($this->events) >= $this->maxBuffer || (time() - $this->lastFlushed) >= $this->flushIntervalSeconds) {
            $this->flush();
        }
    }
}
