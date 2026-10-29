<?php

use Flagsmith\Utils\EventProcessor;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

class EventProcessorTest extends TestCase
{
    private array $requests = [];

    private function eventsApiClient(?Throwable $failure = null): ClientInterface
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')
            ->willReturnCallback(function ($request) use ($failure) {
                $this->requests[] = $request;
                if ($failure) {
                    throw $failure;
                }
                return new Response(202);
            });

        return $client;
    }

    private function sentEvents(): array
    {
        return array_merge(...array_map(
            fn ($request) => json_decode((string) $request->getBody(), true)['events'],
            $this->requests,
        ));
    }

    public function testTrackedEventIsSentToTheEventsApi()
    {
        // Given
        $processor = new EventProcessor('env-key', 'http://events/', client: $this->eventsApiClient());
        $composerData = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true);

        // When
        $processor->trackEvent('checkout_completed', 'user_123', 42, ['plan' => 'premium'], ['currency' => 'GBP']);
        $processor->flush();

        // Then
        $this->assertCount(1, $this->requests);
        $request = $this->requests[0];
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals('http://events/v1/events', (string) $request->getUri());
        $this->assertEquals('env-key', $request->getHeaderLine('X-Environment-Key'));
        $this->assertEquals("flagsmith-php-sdk/{$composerData['version']}", $request->getHeaderLine('Flagsmith-SDK-User-Agent'));
        $event = $this->sentEvents()[0];
        $this->assertGreaterThan(0, $event['timestamp']);
        unset($event['timestamp']);
        $this->assertEquals([
            'event' => 'checkout_completed',
            'feature_name' => null,
            'identifier' => 'user_123',
            'value' => '42',
            'traits' => ['plan' => 'premium'],
            'metadata' => ['currency' => 'GBP', 'sdk_version' => $composerData['version']],
        ], $event);
    }

    public function testEventsAreSentToTheDefaultEventsApi()
    {
        // Given
        $processor = new EventProcessor('env-key', client: $this->eventsApiClient());

        // When
        $processor->trackEvent('signed_up');
        $processor->flush();

        // Then
        $this->assertEquals('https://events.api.flagsmith.com/v1/events', (string) $this->requests[0]->getUri());
    }

    public function testEventValuesAreSentAsText()
    {
        // Given
        $processor = new EventProcessor('env-key', client: $this->eventsApiClient());

        // When
        $processor->trackEvent('purchase', value: 'basket');
        $processor->trackEvent('purchase', value: 9.99);
        $processor->trackEvent('purchase', value: true);
        $processor->trackEvent('purchase');
        $processor->flush();

        // Then
        $this->assertEquals(['basket', '9.99', 'true', null], array_column($this->sentEvents(), 'value'));
    }

    public function testRepeatedExposuresAreSentOnce()
    {
        // Given
        $processor = new EventProcessor('env-key', client: $this->eventsApiClient());

        // When
        $processor->trackExposureEvent('banner', 'user_123', 'treatment', metadata: ['experiment_id' => 1]);
        $processor->trackExposureEvent('banner', 'user_123', 'treatment', metadata: ['experiment_id' => 1]);
        $processor->trackExposureEvent('banner', 'user_123', 'control', metadata: ['experiment_id' => 1]);
        $processor->flush();
        $processor->trackExposureEvent('banner', 'user_123', 'treatment', metadata: ['experiment_id' => 1]);
        $processor->flush();

        // Then
        $events = $this->sentEvents();
        $this->assertEquals(['$flag_exposure', '$flag_exposure', '$flag_exposure'], array_column($events, 'event'));
        $this->assertEquals(['banner', 'banner', 'banner'], array_column($events, 'feature_name'));
        $this->assertEquals(['treatment', 'control', 'treatment'], array_column($events, 'value'));
    }

    public function testEventsAreSentOnceTheBufferIsFull()
    {
        // Given
        $processor = new EventProcessor('env-key', maxBuffer: 2, client: $this->eventsApiClient());

        // When
        $processor->trackEvent('first');
        $processor->trackEvent('second');

        // Then
        $this->assertEquals(['first', 'second'], array_column($this->sentEvents(), 'event'));
    }

    public function testEventsAreSentOnceTheFlushIntervalHasElapsed()
    {
        // Given
        $processor = new EventProcessor('env-key', flushIntervalSeconds: 0, client: $this->eventsApiClient());

        // When
        $processor->trackEvent('first');

        // Then
        $this->assertEquals(['first'], array_column($this->sentEvents(), 'event'));
    }

    public function testBufferedEventsAreSentWhenTheProcessorIsDestroyed()
    {
        // Given
        $processor = new EventProcessor('env-key', client: $this->eventsApiClient());
        $processor->trackEvent('first');

        // When
        unset($processor);

        // Then
        $this->assertEquals(['first'], array_column($this->sentEvents(), 'event'));
    }

    public function testNothingIsSentWithoutEvents()
    {
        // Given
        $processor = new EventProcessor('env-key', client: $this->eventsApiClient());

        // When
        $processor->flush();

        // Then
        $this->assertEmpty($this->requests);
    }

    public function testEventsThatCannotBeSentAreDroppedWithoutFailingTheApplication()
    {
        // Given
        $processor = new EventProcessor(
            'env-key',
            client: $this->eventsApiClient(new ConnectException('Connection refused', new Request('POST', 'http://events/'))),
        );
        $processor->trackEvent('first');

        // When
        $processor->flush();
        $processor->flush();

        // Then
        $this->assertCount(1, $this->requests);
        $this->assertEmpty($processor->events);
    }
}
