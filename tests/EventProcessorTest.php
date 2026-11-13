<?php

use Flagsmith\Utils\EventProcessor;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Psr18Client as SymfonyPsr18Client;
use Symfony\Component\HttpClient\Response\MockResponse;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;

class EventProcessorTest extends TestCase
{
    private array $requests = [];
    private array $waits = [];

    private function eventsApiClient(?Throwable $failure = null): ClientInterface
    {
        return $this->scriptedEventsApiClient([], $failure);
    }

    /**
     * An events API client answering each request with the next of `$responses`, a status code or an
     * exception to throw, then with `$otherwise` (a 202 if null).
     */
    private function scriptedEventsApiClient(array $responses, ?Throwable $otherwise = null): ClientInterface
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')
            ->willReturnCallback(function ($request) use (&$responses, $otherwise) {
                $this->requests[] = $request;
                $response = array_shift($responses) ?? $otherwise ?? 202;
                if ($response instanceof Throwable) {
                    throw $response;
                }
                return new Response($response);
            });

        return $client;
    }

    private function processor(ClientInterface $client, int $maxBuffer = EventProcessor::DEFAULT_MAX_BUFFER): EventProcessor
    {
        return new EventProcessor(
            'env-key',
            'http://events/',
            maxBuffer: $maxBuffer,
            client: $client,
            sleep: function (float $seconds) {
                $this->waits[] = $seconds;
            },
        );
    }

    private function connectionFailure(): ConnectException
    {
        return new ConnectException('Connection reset', new Request('POST', 'http://events/'));
    }

    /**
     * The event each request carried, by request.
     */
    private function sentBatches(): array
    {
        return array_map(
            fn ($request) => array_column(json_decode((string) $request->getBody(), true)['events'], 'event'),
            $this->requests,
        );
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

    public function testTrackingAnEventIntoAFullBufferSendsIt()
    {
        // Given
        $processor = $this->processor($this->scriptedEventsApiClient([]), maxBuffer: 2);

        // When
        $processor->trackEvent('first');
        $processor->trackEvent('second');
        $processor->trackEvent('third');
        $processor->flush();

        // Then
        $this->assertEquals([['first', 'second'], ['third']], $this->sentBatches());
    }

    public function testAFullBufferThatFailsToSendDropsItsOldestEventAfterOneAttempt()
    {
        // Given
        $processor = $this->processor($this->scriptedEventsApiClient([503]), maxBuffer: 2);

        // When
        $processor->trackEvent('first');
        $processor->trackEvent('second');
        $processor->trackEvent('third');
        $processor->flush();

        // Then
        $this->assertEquals([['first', 'second'], ['second', 'third']], $this->sentBatches());
        $this->assertEmpty($this->waits);
    }

    public function testAFullBufferDropsTheOldestEventOfAKeptBatch()
    {
        // Given
        $processor = $this->processor($this->scriptedEventsApiClient([503, 503, 503]), maxBuffer: 2);
        $processor->trackEvent('first');
        $processor->trackEvent('second');
        $processor->flush();

        // When
        $processor->trackEvent('third');
        $processor->flush();

        // Then
        $batches = $this->sentBatches();
        $this->assertEquals(['second', 'third'], end($batches));
    }

    public function testADroppedExposureCanBeTrackedAgain()
    {
        // Given
        $processor = $this->processor($this->scriptedEventsApiClient([503, 503, 503]), maxBuffer: 1);
        $processor->trackExposureEvent('banner', 'user_123', 'treatment');
        $processor->flush();
        $processor->trackEvent('checkout');

        // When
        $processor->trackExposureEvent('banner', 'user_123', 'treatment');
        $processor->flush();

        // Then
        $batches = $this->sentBatches();
        $this->assertEquals([['checkout'], ['$flag_exposure']], array_slice($batches, -2));
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

    public function testDestroyingTheProcessorMakesASingleAttempt()
    {
        // Given
        $processor = $this->processor($this->eventsApiClient($this->connectionFailure()));
        $processor->trackEvent('first');

        // When
        unset($processor);

        // Then
        $this->assertCount(1, $this->requests);
        $this->assertEmpty($this->waits);
    }

    public function testAFlushOnceTheFlushIntervalHasElapsedMakesASingleAttempt()
    {
        // Given
        $processor = new EventProcessor(
            'env-key',
            flushIntervalSeconds: 0,
            client: $this->eventsApiClient($this->connectionFailure()),
            sleep: function (float $seconds) {
                $this->waits[] = $seconds;
            },
        );

        // When
        $processor->trackEvent('first');

        // Then
        $this->assertCount(1, $this->requests);
        $this->assertEmpty($this->waits);
        $this->assertEquals(['first'], array_column($processor->events, 'event'));
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

    public function testABatchIsRetriedUntilTheEventsApiAcceptsIt()
    {
        // Given
        $processor = $this->processor($this->scriptedEventsApiClient([503, $this->connectionFailure()]));
        $processor->trackEvent('checkout');

        // When
        $processor->flush();

        // Then
        $this->assertEquals([['checkout'], ['checkout'], ['checkout']], $this->sentBatches());
        $this->assertEmpty($processor->events);
        $this->assertCount(2, $this->waits);
        $this->assertLessThanOrEqual(1, $this->waits[0]);
        $this->assertLessThanOrEqual(2, $this->waits[1]);
    }

    public static function transientFailuresProvider(): array
    {
        return [
            'connection error' => [null],
            '408' => [408],
            '429' => [429],
            '502' => [502],
            '503' => [503],
            '504' => [504],
        ];
    }

    /**
     * @dataProvider transientFailuresProvider
     */
    public function testABatchThatKeepsFailingIsSentWithTheNextFlushAheadOfNewerEvents(?int $status)
    {
        // Given
        $failure = $status ?? $this->connectionFailure();
        $processor = $this->processor($this->scriptedEventsApiClient([$failure, $failure, $failure]));
        $processor->trackEvent('add_to_cart');
        $processor->flush();

        // When
        $processor->trackEvent('checkout');
        $processor->flush();

        // Then
        $this->assertEquals(
            [['add_to_cart'], ['add_to_cart'], ['add_to_cart'], ['add_to_cart', 'checkout']],
            $this->sentBatches(),
        );
        $this->assertEmpty($processor->events);
    }

    public function testABatchWhoseLastAttemptGetsAServerErrorIsDropped()
    {
        // Given
        $processor = $this->processor($this->scriptedEventsApiClient([503, 503, 500]));
        $processor->trackEvent('add_to_cart');
        $processor->flush();

        // When
        $processor->trackEvent('checkout');
        $processor->flush();

        // Then
        $this->assertEquals([['add_to_cart'], ['add_to_cart'], ['add_to_cart'], ['checkout']], $this->sentBatches());
    }

    public function testABatchWhoseLastAttemptFailsOtherwiseIsKeptAfterServerErrors()
    {
        // Given
        $processor = $this->processor($this->scriptedEventsApiClient([500, 500, 503]));
        $processor->trackEvent('add_to_cart');

        // When
        $processor->flush();

        // Then
        $this->assertCount(3, $this->requests);
        $this->assertEquals(['add_to_cart'], array_column($processor->events, 'event'));
    }

    public static function rejectingStatusesProvider(): array
    {
        return ['400' => [400], '404' => [404], '413' => [413], '501' => [501], '505' => [505]];
    }

    /**
     * @dataProvider rejectingStatusesProvider
     */
    public function testABatchTheEventsApiRejectsIsDroppedWithoutRetrying(int $status)
    {
        // Given
        $processor = $this->processor($this->scriptedEventsApiClient([$status]));
        $processor->trackEvent('add_to_cart');
        $processor->flush();

        // When
        $processor->trackEvent('checkout');
        $processor->flush();

        // Then
        $this->assertEquals([['add_to_cart'], ['checkout']], $this->sentBatches());
        $this->assertEmpty($this->waits);
    }

    public static function refusingStatusesProvider(): array
    {
        return ['401' => [401], '403' => [403]];
    }

    /**
     * @dataProvider refusingStatusesProvider
     */
    public function testAnEventsApiRefusingTheEnvironmentKeyStopsEventTracking(int $status)
    {
        // Given
        $processor = $this->processor($this->scriptedEventsApiClient([$status]));
        $processor->trackEvent('add_to_cart');
        $processor->flush();

        // When
        $processor->trackEvent('checkout');
        $processor->flush();

        // Then
        $this->assertEquals([['add_to_cart']], $this->sentBatches());
        $this->assertEmpty($processor->events);
    }

    public function testEventsThatCannotBeSentNeverFailTheApplication()
    {
        // Given
        $processor = $this->processor($this->eventsApiClient(new \RuntimeException('Unexpected')));
        $processor->trackEvent('first');

        // When
        $processor->flush();

        // Then
        $this->assertCount(EventProcessor::MAX_ATTEMPTS, $this->requests);
    }

    public function testAGuzzleClientTimesOutEachRequest()
    {
        // Given
        $client = $this->createMock(GuzzleClient::class);
        $client->expects($this->once())
            ->method('send')
            ->with($this->anything(), ['timeout' => EventProcessor::REQUEST_TIMEOUT_SECONDS, 'http_errors' => false])
            ->willReturn(new Response(202));
        $processor = $this->processor($client);
        $processor->trackEvent('checkout');

        // When / Then
        $processor->flush();
    }

    public function testASymfonyClientTimesOutEachRequest()
    {
        // Given
        $options = [];
        $client = new SymfonyPsr18Client(new MockHttpClient(function ($method, $url, $requestOptions) use (&$options) {
            $options[] = $requestOptions;
            return new MockResponse('', ['http_code' => 202]);
        }));
        $processor = $this->processor($client);
        $processor->trackEvent('checkout');

        // When
        $processor->flush();

        // Then
        $this->assertCount(1, $options);
        $this->assertEquals(EventProcessor::REQUEST_TIMEOUT_SECONDS, $options[0]['timeout']);
        $this->assertEquals(EventProcessor::REQUEST_TIMEOUT_SECONDS, $options[0]['max_duration']);
    }

    public static function httpClientsProvider(): array
    {
        return [
            'Guzzle' => [fn () => new GuzzleClient()],
            'Symfony' => [fn () => new SymfonyPsr18Client(HttpClient::create())],
        ];
    }

    /**
     * @dataProvider httpClientsProvider
     */
    public function testCloseReturnsWithinItsTimeoutFromAnEventsApiThatNeverAnswers(\Closure $createClient)
    {
        // Given an events API accepting connections, but never answering
        $server = stream_socket_server('tcp://127.0.0.1:0');
        $processor = new EventProcessor('env-key', 'http://' . stream_socket_get_name($server, false) . '/', client: $createClient());
        $processor->trackEvent('checkout');
        $started = microtime(true);

        // When
        $processor->close(0.5);

        // Then
        $this->assertLessThan(1.5, microtime(true) - $started);
        fclose($server);
    }

    public function testCloseStartsNoRetryAfterItsTimeoutWithAnotherClient()
    {
        // Given a PSR-18 client that cannot be given a timeout, and gives up after its own
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')
            ->willReturnCallback(function ($request) {
                $this->requests[] = $request;
                usleep(300000);
                throw new ConnectException('Timed out', $request);
            });
        $processor = new EventProcessor('env-key', client: $client);
        $processor->trackEvent('checkout');
        $started = microtime(true);

        // When
        $processor->close(0.2);

        // Then
        $this->assertCount(1, $this->requests);
        $this->assertLessThan(1, microtime(true) - $started);
    }

    public function testCloseSendsTheBufferedEventsRetriesIncluded()
    {
        // Given
        $processor = $this->processor($this->scriptedEventsApiClient([503]));
        $processor->trackEvent('checkout');

        // When
        $processor->close(5);

        // Then
        $this->assertEquals([['checkout'], ['checkout']], $this->sentBatches());
    }

    public function testCloseReturnsWithinItsTimeoutWhenTheEventsApiDoesNotAnswer()
    {
        // Given
        $timeouts = [];
        $client = $this->createMock(GuzzleClient::class);
        $client->method('send')
            ->willReturnCallback(function ($request, $options) use (&$timeouts) {
                $timeouts[] = $options['timeout'];
                usleep((int) ($options['timeout'] * 1000000));
                throw new ConnectException('Timed out', $request);
            });
        $processor = new EventProcessor('env-key', client: $client);
        $processor->trackEvent('checkout');
        $started = microtime(true);

        // When
        $processor->close(0.2);

        // Then
        $this->assertLessThan(1, microtime(true) - $started);
        $this->assertCount(1, $timeouts);
        $this->assertLessThanOrEqual(0.2, $timeouts[0]);
    }

    public function testAClosedProcessorTracksNoMoreEvents()
    {
        // Given
        $processor = $this->processor($this->eventsApiClient());
        $processor->close(1);

        // When
        $processor->trackEvent('checkout');
        $processor->flush();
        unset($processor);

        // Then
        $this->assertEmpty($this->requests);
    }
}
