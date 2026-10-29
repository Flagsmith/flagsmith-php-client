<?php

use Flagsmith\Engine\Utils\Types\Context\EvaluationContext;
use Flagsmith\Exceptions\FlagsmithAPIError;
use Flagsmith\Flagsmith;
use Flagsmith\Models\DefaultFlag;
use Flagsmith\Utils\EventProcessor;
use FlagsmithTest\ClientFixtures;
use FlagsmithTest\Offline\FakeOfflineHandler;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\StreamFactoryInterface;

class FlagsmithClientTest extends TestCase
{
    public function testUpdateEnvironmentSetsLocalEvaluationContext(): void
    {
        // Given
        $flagsmith = new Flagsmith('ser.api_key', environmentTtl: 1);
        $flagsmith->withClient(ClientFixtures::getMockClient());

        // When
        $flagsmith->updateEnvironment();

        // Then
        $context = $flagsmith->getLocalEvaluationContext();
        $this->assertInstanceOf(EvaluationContext::class, $context);
        $this->assertEquals('Test environment', $context->environment->name);
    }

    public function testGetEnvironmentFlagsCallsApiWhenLocalEvaluationDisabled(): void
    {
        // Given
        $flagsmith = new Flagsmith('api_key');
        $flagsmith->withClient(ClientFixtures::getMockClient());

        // When
        $allFlags = $flagsmith->getEnvironmentFlags()->allFlags();

        // Then
        $this->assertTrue($allFlags[0]->enabled);
        $this->assertEquals($allFlags[0]->value, 'some-value');
        $this->assertEquals($allFlags[0]->feature_name, 'some_feature');
    }

    public function testGetEnvironmentFlagsUsesLocalContextWhenLocalEvaluationEnabled(): void
    {
        // Given
        $flagsmith = new Flagsmith('ser.api_key', environmentTtl: 1);
        $flagsmith->withClient(ClientFixtures::getMockClient());

        // When
        $allFlags = $flagsmith->getEnvironmentFlags()->allFlags();

        // Then
        $this->assertTrue($allFlags[0]->enabled);
        $this->assertEquals($allFlags[0]->value, 'some-value');
        $this->assertEquals($allFlags[0]->feature_name, 'some_feature');
    }

    public function testGetIdentityFlagsCallsApiWhenNoLocalEnvironmentNoTraits()
    {
        $flagsmith = (new Flagsmith('api_key'))
            ->withClient(ClientFixtures::getMockClient());

        $identifier = 'identifer';

        $identityFlags = $flagsmith->getIdentityFlags($identifier)->allFlags();

        $this->assertTrue($identityFlags[0]->enabled);
        $this->assertEquals($identityFlags[0]->value, 'some-value');
        $this->assertEquals($identityFlags[0]->feature_name, 'some_feature');
    }

    public function testGetIdentityFlagsCallsApiWhenNoLocalEnvironmentWithTraits()
    {
        $streamMock = $this->createMock(StreamFactoryInterface::class);

        $flagsmith = (new Flagsmith('api_key'))
            ->withClient(ClientFixtures::getMockClient())
            ->withStreamFactory($streamMock);

        $identifier = 'identifer';
        $traits = (object)['some_trait' => 'some-value', 'transient_trait' => (object)['transient' => true, 'value' => 'some-transient-value']];

        $streamMock->expects($this->once())
            ->method('createStream')
            ->with($this->equalTo(json_encode([
                'identifier' => $identifier,
                'traits' => [
                    ['trait_key' => 'some_trait', 'trait_value' => 'some-value'],
                    ['trait_key' => 'transient_trait', 'trait_value' => 'some-transient-value', 'transient' => true],
                ],
            ])));

        $identityFlags = $flagsmith->getIdentityFlags($identifier, $traits)->allFlags();

        $this->assertTrue($identityFlags[0]->enabled);
        $this->assertEquals($identityFlags[0]->value, 'some-value');
        $this->assertEquals($identityFlags[0]->feature_name, 'some_feature');
    }

    public function testGetIdentityFlagsCallsApiWhenNoLocalEnvironmentTransient()
    {
        $streamMock = $this->createMock(StreamFactoryInterface::class);

        $flagsmith = (new Flagsmith('api_key'))
            ->withClient(ClientFixtures::getMockClient())
            ->withStreamFactory($streamMock);

        $identifier = 'identifer';
        $traits = (object)['some_trait' => 'some-value'];

        $streamMock->expects($this->once())
            ->method('createStream')
            ->with($this->equalTo(json_encode([
                'identifier' => $identifier,
                'traits' => [
                    ['trait_key' => 'some_trait', 'trait_value' => 'some-value'],
                ],
                'transient' => true,
            ])));

        $flagsmith->getIdentityFlags($identifier, $traits, true);
    }

    public function testRequestConnectionErrorRaisesFlagsmithApiError()
    {
        $flagsmith = (new Flagsmith('api_key'))
            ->withClient(ClientFixtures::getMockClient(null, false));

        $this->expectException(FlagsmithAPIError::class);
        $flagsmith->getEnvironmentFlags();
    }

    public function testNon200ResponseRaisesFlagsmithApiError()
    {
        $handlerBuilder = ClientFixtures::getHandlerBuilder();
        $handlerBuilder->addRoute(
            ClientFixtures::getRouteBuilder()->new()
                ->withMethod('GET')
                ->withPath('/api/v1/flags/')
                ->withResponse(new Response(400))
                ->build()
        );

        $flagsmith = (new Flagsmith('api_key'))
            ->withClient(ClientFixtures::getMockClient($handlerBuilder, false));

        $this->expectException(FlagsmithAPIError::class);
        $flagsmith->getEnvironmentFlags();
    }

    public function testDefaultFlagIsUsedWhenNoEnvironmentFlagsReturned()
    {
        $handlerBuilder = ClientFixtures::getHandlerBuilder();
        $handlerBuilder->addRoute(
            ClientFixtures::getRouteBuilder()->new()
                ->withMethod('GET')
                ->withPath('/api/v1/flags/')
                ->withResponse(new Response(200, [], '[]'))
                ->build()
        );

        $featureName = 'some_feature';
        $defaultFlag = (new DefaultFlag())
            ->withEnabled(true)
            ->withValue('some-default-value');

        $defaultFlagHandler = function (string $featureName) use ($defaultFlag) {
            return $defaultFlag;
        };

        $flagsmith = (new Flagsmith('api_key'))
            ->withDefaultFlagHandler($defaultFlagHandler)
            ->withClient(ClientFixtures::getMockClient($handlerBuilder, false));

        $flags = $flagsmith->getEnvironmentFlags();

        $flag = $flags->getFlag($featureName);

        $this->assertTrue($flag->is_default);
        $this->assertEquals($flag->enabled, $defaultFlag->getEnabled());
        $this->assertEquals($flag->value, $defaultFlag->getValue());
    }

    public function testDefaultFlagIsNotUsedWhenEnvironmentFlagsReturned()
    {
        $featureName = 'some_feature';
        $defaultFlag = (new DefaultFlag())
            ->withEnabled(true)
            ->withValue('some-default-value');

        $defaultFlagHandler = function (string $featureName) use ($defaultFlag) {
            return $defaultFlag;
        };

        $flagsmith = (new Flagsmith('api_key'))
            ->withDefaultFlagHandler($defaultFlagHandler)
            ->withClient(ClientFixtures::getMockClient());

        $flags = $flagsmith->getEnvironmentFlags();

        $flag = $flags->getFlag($featureName);

        $this->assertFalse($flag->is_default);
        $this->assertEquals($flag->enabled, $defaultFlag->getEnabled());
        $this->assertEquals($flag->value, 'some-value');
    }

    public function testDefaultFlagIsUsedWhenNoIdentityFlagsReturned()
    {
        $responseData = ['flags' => [], 'traits' => []];
        $handlerBuilder = ClientFixtures::getHandlerBuilder();
        $handlerBuilder->addRoute(
            ClientFixtures::getRouteBuilder()->new()
            ->withMethod('POST')
            ->withPath('/api/v1/identities/')
            ->withResponse(new Response(200, [], json_encode($responseData)))
            ->build()
        );

        $featureName = 'some_feature';
        $defaultFlag = (new DefaultFlag())
            ->withEnabled(true)
            ->withValue('some-default-value');

        $defaultFlagHandler = function (string $featureName) use ($defaultFlag) {
            return $defaultFlag;
        };

        $flagsmith = (new Flagsmith('api_key'))
            ->withDefaultFlagHandler($defaultFlagHandler)
            ->withClient(ClientFixtures::getMockClient($handlerBuilder, false));

        $flags = $flagsmith->getIdentityFlags('identifier');

        $flag = $flags->getFlag($featureName);

        $this->assertTrue($flag->is_default);
        $this->assertEquals($flag->enabled, $defaultFlag->getEnabled());
        $this->assertEquals($flag->value, $defaultFlag->getValue());
    }

    public function testDefaultFlagIsNotUsedWhenIdentityFlagsReturned()
    {
        $featureName = 'some_feature';
        $defaultFlag = (new DefaultFlag())
            ->withEnabled(true)
            ->withValue('some-default-value');

        $defaultFlagHandler = function (string $featureName) use ($defaultFlag) {
            return $defaultFlag;
        };

        $flagsmith = (new Flagsmith('api_key'))
            ->withDefaultFlagHandler($defaultFlagHandler)
            ->withClient(ClientFixtures::getMockClient());

        $flags = $flagsmith->getIdentityFlags('identifier');

        $flag = $flags->getFlag($featureName);

        $this->assertFalse($flag->is_default);
        $this->assertNotEquals($flag->value, $defaultFlag->getValue());
        $this->assertEquals($flag->value, 'some-value');
    }

    public function testDefaultFlagsAreUsedIfApiErrorAndDefaultFlagHandlerGiven()
    {
        $defaultFlag = (new DefaultFlag())
            ->withEnabled(true)
            ->withValue('some-default-value');

        $defaultFlagHandler = function (string $featureName) use ($defaultFlag) {
            return $defaultFlag;
        };

        $flagsmith = (new Flagsmith('api_key'))
            ->withDefaultFlagHandler($defaultFlagHandler)
            ->withClient(ClientFixtures::getMockClient(null, false));

        $flags = $flagsmith->getEnvironmentFlags();

        $this->assertEquals($flags->getFlag('some-feature'), $defaultFlag);
    }

    public function testLocalEvaluationRequiresServerKey()
    {
        $this->expectException(ValueError::class);
        $flagsmith = (new Flagsmith('not-a-server-key', Flagsmith::DEFAULT_API_URL, null, 10))
            ->withClient(ClientFixtures::getMockClient());

        $flagsmith->getEnvironmentFlags();
    }

    public function testGetIdentitySegmentsNoTraits()
    {
        foreach (ClientFixtures::localEvalFlagsmith() as $flagsmith) {
            $identifier = 'identifier';

            $segments = $flagsmith->getIdentitySegments($identifier);
            $this->assertEquals($segments, []);
        }
    }

    public function testGetIdentitySegmentsWithValidTrait()
    {
        foreach (ClientFixtures::localEvalFlagsmith() as $flagsmith) {
            $identifier = 'overridden-id';  // Creates a virtual segment for the identity override
            $traits = (object)['foo' => 'bar'];  // Matches the segment

            $segments = $flagsmith->getIdentitySegments($identifier, $traits);
            $this->assertEquals(count($segments), 1);
            $this->assertEquals($segments[0]->getName(), 'Test segment');
            $this->assertSame(1, $segments[0]->id);
        }
    }

    public function testLocalEvaluationGetIdentityOverride()
    {
        foreach (ClientFixtures::localEvalFlagsmith() as $flagsmith) {
            $identifier = 'overridden-id';
            $featureName = 'some_feature';

            $identityFlags = $flagsmith->getIdentityFlags($identifier);

            $flag = $identityFlags->getFlag($featureName);

            $this->assertEquals($flag->enabled, false);
            $this->assertEquals($flag->value, 'some-overridden-value');
        }
    }

    public function testOfflineMode()
    {
        // Given
        $offlineHandler = new FakeOfflineHandler();
        $flagsmith = new Flagsmith(offlineMode: true, offlineHandler: $offlineHandler);

        // When
        $environmentFlags = $flagsmith->getEnvironmentFlags();
        $identityFlags = $flagsmith->getIdentityFlags('my-identity');

        // Then
        $this->assertEquals($environmentFlags->getFlag('some_feature')->enabled, true);
        $this->assertEquals($environmentFlags->getFlag('some_feature')->value, 'some-value');

        $this->assertEquals($identityFlags->getFlag('some_feature')->enabled, true);
        $this->assertEquals($identityFlags->getFlag('some_feature')->value, 'some-value');
    }

    public function testFlagsmithUseOfflineHandlerIfSetAndNoApiResponse()
    {
        // Given
        $handlerBuilder = ClientFixtures::getHandlerBuilder();
        $handlerBuilder->addRoute(
            ClientFixtures::getRouteBuilder()->new()
            ->withMethod('POST')
            ->withPath('/api/v1/identities/')
            ->withResponse(new Response(500))
            ->build()
        );
        $handlerBuilder->addRoute(
            ClientFixtures::getRouteBuilder()->new()
            ->withMethod('GET')
            ->withPath('/api/v1/flags/')
            ->withResponse(new Response(500))
            ->build()
        );

        $flagsmith = (new Flagsmith(apiKey: 'some-key', offlineHandler: new FakeOfflineHandler()))
            ->withClient(ClientFixtures::getMockClient($handlerBuilder, false));

        // When
        $environmentFlags = $flagsmith->getEnvironmentFlags();
        $identityFlags = $flagsmith->getIdentityFlags('my-identity');

        // Then
        $this->assertEquals($environmentFlags->getFlag('some_feature')->enabled, true);
        $this->assertEquals($environmentFlags->getFlag('some_feature')->value, 'some-value');

        $this->assertEquals($identityFlags->getFlag('some_feature')->enabled, true);
        $this->assertEquals($identityFlags->getFlag('some_feature')->value, 'some-value');
    }

    public function testCannotUseOfflineModeWithoutOfflineHandler()
    {
        // Given
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('offlineHandler must be provided to use offline mode.');

        // When
        new Flagsmith(offlineMode:true, offlineHandler:null);
    }

    public function testCannotUseDefaultHandlerAndOfflineHandler()
    {
        // Given
        $defaultFlag = (new DefaultFlag())
            ->withEnabled(true)
            ->withValue('some-default-value');

        $defaultFlagHandler = function (string $featureName) use ($defaultFlag) {
            return $defaultFlag;
        };

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Cannot use both defaultFlagHandler and offlineHandler.');

        $offlineHandler = new FakeOfflineHandler();

        // When
        new Flagsmith(defaultFlagHandler:$defaultFlagHandler, offlineHandler:$offlineHandler);
    }

    public function testCannotCreateFlagsmithClientInRemoteEvaluationWithoutApiKey()
    {
        // Given
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('apiKey is required');

        // When
        new Flagsmith();
    }

    public function testOfflineHandlerUsedAsFallbackForLocalEvaluation()
    {
        // Given
        $handlerBuilder = ClientFixtures::getHandlerBuilder();
        $handlerBuilder->addRoute(
            ClientFixtures::getRouteBuilder()->new()
            ->withMethod('GET')
            ->withPath('/api/v1/environment-document/')
            ->withResponse(new Response(500))
            ->build()
        );

        $offlineHandler = new FakeOfflineHandler();
        $flagsmith = (new Flagsmith(apiKey: 'ser.some-key', environmentTtl: 3, offlineHandler: $offlineHandler));

        // When
        $environmentFlags = $flagsmith->getEnvironmentFlags();
        $identityFlags = $flagsmith->getIdentityFlags('my-identity');

        // Then
        $this->assertEquals($environmentFlags->getFlag('some_feature')->enabled, true);
        $this->assertEquals($environmentFlags->getFlag('some_feature')->value, 'some-value');

        $this->assertEquals($identityFlags->getFlag('some_feature')->enabled, true);
        $this->assertEquals($identityFlags->getFlag('some_feature')->value, 'some-value');
    }

    public function testApiRequestsIncludeUserAgentHeader()
    {
        $capturedRequest = null;
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->with($this->callback(function ($request) use (&$capturedRequest) {
                $capturedRequest = $request;
                return true;
            }))
            ->willReturn(
                new Response(200, ['Content-Type' => 'application/json'], file_get_contents(__DIR__ . '/Data/flags.json'))
            );

        $flagsmith = (new Flagsmith('api_key'))
            ->withClient($mockClient);

        $flagsmith->getEnvironmentFlags();

        $this->assertNotNull($capturedRequest);
        $this->assertTrue($capturedRequest->hasHeader('User-Agent'));
        $userAgent = $capturedRequest->getHeaderLine('User-Agent');
        $composerData = json_decode(file_get_contents(__DIR__ . '/../composer.json'), true);
        $expectedVersion = $composerData['version'] ?? 'unknown';
        $expectedUserAgent = "flagsmith-php-sdk/{$expectedVersion}";
        $this->assertEquals($expectedUserAgent, $userAgent);
    }

    public function testGetEnvironmentFlagsOmitsSegmentsFromEvaluation()
    {
        // Given
        $flagsmith = (new Flagsmith('ser.api_key', environmentTtl: 1))
            ->withClient(ClientFixtures::getMockClientWithSegmentOverride());
        $flagsmith->updateEnvironment();

        // When
        $environmentFlags = $flagsmith->getEnvironmentFlags();
        $identityFlags = $flagsmith->getIdentityFlags('test-identity');

        // Then
        $environmentFlag = $environmentFlags->getFlag('test_feature');
        $this->assertTrue($environmentFlag->enabled);
        $this->assertEquals('environment-default-value', $environmentFlag->value);

        $identityFlag = $identityFlags->getFlag('test_feature');
        $this->assertFalse($identityFlag->enabled);
        $this->assertEquals('segment-override-value', $identityFlag->value);
    }

    private function identityInExperimentClient(): ClientInterface
    {
        $handlerBuilder = ClientFixtures::getHandlerBuilder();
        $handlerBuilder->addRoute(
            ClientFixtures::getRouteBuilder()->new()
                ->withMethod('POST')
                ->withPath('/api/v1/identities/')
                ->withFileResponse(__DIR__ . '/Data/identities_in_experiment.json')
                ->build()
        );

        return ClientFixtures::getMockClient($handlerBuilder, false);
    }

    private function eventsApiClient(array &$events): ClientInterface
    {
        $client = $this->createMock(ClientInterface::class);
        $client->method('sendRequest')
            ->willReturnCallback(function ($request) use (&$events) {
                array_push($events, ...json_decode((string) $request->getBody(), true)['events']);
                return new Response(202);
            });

        return $client;
    }

    public function testGetExperimentFlagRecordsTheExposureOfAnIdentityInAnExperiment()
    {
        // Given
        $events = [];
        $flagsmith = (new Flagsmith('api_key'))
            ->withClient($this->identityInExperimentClient())
            ->withEventProcessor(new EventProcessor('api_key', client: $this->eventsApiClient($events)));

        // When
        $flag = $flagsmith->getExperimentFlag('banner', 'user_123', (object) ['plan' => 'premium']);
        $flagsmith->flushEvents();

        // Then
        $this->assertTrue($flag->getEnabled());
        $this->assertEquals('hello treatment', $flag->getValue());
        $this->assertEquals('treatment', $flag->getVariant());
        $this->assertEquals(7, $flag->getExperiment()->getId());
        $this->assertEquals('banner experiment', $flag->getExperiment()->getName());
        $this->assertTrue($flag->getExperiment()->getInExperiment());
        $this->assertCount(1, $events);
        $this->assertEquals('$flag_exposure', $events[0]['event']);
        $this->assertEquals('banner', $events[0]['feature_name']);
        $this->assertEquals('user_123', $events[0]['identifier']);
        $this->assertEquals('treatment', $events[0]['value']);
        $this->assertEquals(['plan' => 'premium'], $events[0]['traits']);
        $this->assertEquals(7, $events[0]['metadata']['experiment_id']);
    }

    public static function flagsNotExposedProvider(): array
    {
        return [
            'identity not enrolled in the experiment' => ['checkout'],
            'flag disabled' => ['paused_banner'],
            'feature without an experiment' => ['footer'],
        ];
    }

    /**
     * @dataProvider flagsNotExposedProvider
     */
    public function testGetExperimentFlagDoesNotRecordAnExposureOutsideARunningExperiment(string $featureName)
    {
        // Given
        $events = [];
        $flagsmith = (new Flagsmith('api_key'))
            ->withClient($this->identityInExperimentClient())
            ->withEventProcessor(new EventProcessor('api_key', client: $this->eventsApiClient($events)));

        // When
        $flag = $flagsmith->getExperimentFlag($featureName, 'user_123');
        $flagsmith->flushEvents();

        // Then
        $this->assertEquals($featureName, $flag->getFeatureName());
        $this->assertEmpty($events);
    }

    public function testGetExperimentFlagDoesNotRecordAnExposureForADefaultFlag()
    {
        // Given
        $events = [];
        $defaultFlag = (new DefaultFlag())->withEnabled(true)->withValue('default');
        $flagsmith = (new Flagsmith('api_key', defaultFlagHandler: fn (string $featureName) => $defaultFlag))
            ->withClient($this->identityInExperimentClient())
            ->withEventProcessor(new EventProcessor('api_key', client: $this->eventsApiClient($events)));

        // When
        $flag = $flagsmith->getExperimentFlag('unknown_feature', 'user_123');
        $flagsmith->flushEvents();

        // Then
        $this->assertSame($defaultFlag, $flag);
        $this->assertEmpty($events);
    }

    public function testTrackEventSendsTheEventWithTraitValues()
    {
        // Given
        $events = [];
        $flagsmith = (new Flagsmith('api_key'))
            ->withEventProcessor(new EventProcessor('api_key', client: $this->eventsApiClient($events)));
        $traits = (object) ['plan' => 'premium', 'session' => (object) ['value' => 'abc', 'transient' => true]];

        // When
        $flagsmith->trackEvent('checkout_completed', 'user_123', 42, $traits, ['currency' => 'GBP']);
        $flagsmith->flushEvents();

        // Then
        $this->assertCount(1, $events);
        $this->assertEquals('checkout_completed', $events[0]['event']);
        $this->assertEquals('user_123', $events[0]['identifier']);
        $this->assertEquals('42', $events[0]['value']);
        $this->assertEquals(['plan' => 'premium', 'session' => 'abc'], $events[0]['traits']);
        $this->assertEquals('GBP', $events[0]['metadata']['currency']);
    }

    public function testTrackEventRejectsReservedEventNames()
    {
        // Given
        $flagsmith = (new Flagsmith('api_key'))
            ->withEventProcessor(new EventProcessor('api_key', client: $this->createMock(ClientInterface::class)));

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Event name "$purchase" uses the reserved "$" prefix but is not a known system event.');

        // When
        $flagsmith->trackEvent('$purchase');
    }

    public function testTrackExposureEventSendsAFlagExposure()
    {
        // Given
        $events = [];
        $flagsmith = (new Flagsmith('api_key'))
            ->withEventProcessor(new EventProcessor('api_key', client: $this->eventsApiClient($events)));

        // When
        $flagsmith->trackExposureEvent('banner', 'user_123', 'control', metadata: ['experiment_id' => 1]);
        $flagsmith->flushEvents();

        // Then
        $this->assertCount(1, $events);
        $this->assertEquals('$flag_exposure', $events[0]['event']);
        $this->assertEquals('banner', $events[0]['feature_name']);
        $this->assertEquals('user_123', $events[0]['identifier']);
        $this->assertEquals('control', $events[0]['value']);
        $this->assertEquals(1, $events[0]['metadata']['experiment_id']);
    }

    public function testTrackExposureEventRequiresAnIdentifier()
    {
        // Given
        $flagsmith = (new Flagsmith('api_key'))
            ->withEventProcessor(new EventProcessor('api_key', client: $this->createMock(ClientInterface::class)));

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('An exposure requires an identifier to reconcile with conversion events.');

        // When
        $flagsmith->trackExposureEvent('banner', '', 'control');
    }

    public static function eventMethodsProvider(): array
    {
        return [
            'getExperimentFlag' => [fn (Flagsmith $flagsmith) => $flagsmith->getExperimentFlag('banner', 'user_123'), 'Events must be enabled to use experiment flags.'],
            'trackEvent' => [fn (Flagsmith $flagsmith) => $flagsmith->trackEvent('purchase'), 'Events must be enabled to track events.'],
            'trackExposureEvent' => [fn (Flagsmith $flagsmith) => $flagsmith->trackExposureEvent('banner', 'user_123'), 'Events must be enabled to track exposure events.'],
        ];
    }

    /**
     * @dataProvider eventMethodsProvider
     */
    public function testEventsMustBeEnabledToRecordEvents(\Closure $recordEvent, string $message)
    {
        // Given
        $flagsmith = new Flagsmith('api_key');

        $this->expectException(ValueError::class);
        $this->expectExceptionMessage($message);

        // When
        $recordEvent($flagsmith);
    }

    public function testFlushEventsDoesNothingWithoutEvents()
    {
        // Given
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->never())->method('sendRequest');
        $flagsmith = (new Flagsmith('api_key'))->withClient($client);

        // When / Then
        $flagsmith->flushEvents();
    }

    public function testEventsCanBeEnabledWhenCreatingTheClient()
    {
        // Given
        $flagsmith = new Flagsmith('api_key', enableEvents: true, eventsApiUrl: 'http://127.0.0.1:1/');

        // When
        $flagsmith->trackEvent('purchase');
        $flagsmith->flushEvents();

        // Then
        $this->addToAssertionCount(1);
    }

    public function testCannotUseEventsInOfflineMode()
    {
        // Given
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Cannot use events in offline mode.');

        // When
        new Flagsmith(offlineMode: true, offlineHandler: new FakeOfflineHandler(), enableEvents: true);
    }
}
