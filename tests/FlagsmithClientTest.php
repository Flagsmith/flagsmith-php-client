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

    public function testUpdateEnvironmentPaginatesIdentityOverrides(): void
    {
        // Given
        $pageTwoId = 'identity_override:1:page-2';
        $pageThreeId = 'identity_override:1:page-3';
        $nextLink = fn (string $pageId) => sprintf(
            '</api/v1/environment-document/?page_id=%s>; rel="next"',
            urlencode($pageId)
        );
        $overridePage = fn (string $identifier) => json_encode([
            'identity_overrides' => [[
                'identifier' => $identifier,
                'identity_features' => [[
                    'feature' => ['id' => 1, 'name' => 'some_feature', 'type' => 'STANDARD'],
                    'feature_state_value' => 'some-overridden-value',
                    'enabled' => false,
                ]],
            ]],
        ]);

        $requests = [];
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->expects($this->exactly(3))
            ->method('sendRequest')
            ->with($this->callback(function ($request) use (&$requests) {
                $requests[] = $request;
                return true;
            }))
            ->willReturnOnConsecutiveCalls(
                new Response(200, ['Link' => $nextLink($pageTwoId)], file_get_contents(__DIR__ . '/Data/environment.json')),
                new Response(200, ['Link' => $nextLink($pageThreeId)], $overridePage('page-2-id')),
                new Response(200, [], $overridePage('page-3-id')),
            );

        $flagsmith = (new Flagsmith('ser.api_key', environmentTtl: 1))
            ->withClient($mockClient);

        // When
        $flagsmith->updateEnvironment();

        // Then
        parse_str($requests[1]->getUri()->getQuery(), $secondPageQuery);
        $this->assertEquals($pageTwoId, $secondPageQuery['page_id']);
        parse_str($requests[2]->getUri()->getQuery(), $thirdPageQuery);
        $this->assertEquals($pageThreeId, $thirdPageQuery['page_id']);

        foreach (['overridden-id', 'page-2-id', 'page-3-id'] as $identifier) {
            $flag = $flagsmith->getIdentityFlags($identifier)->getFlag('some_feature');
            $this->assertEquals('some-overridden-value', $flag->value);
        }
        $this->assertEquals('some-value', $flagsmith->getEnvironmentFlags()->getFlag('some_feature')->value);
    }

    public function testUpdateEnvironmentWithoutNextLinkIssuesSingleRequest(): void
    {
        // Given
        $mockClient = $this->createMock(ClientInterface::class);
        $mockClient->expects($this->once())
            ->method('sendRequest')
            ->willReturn(new Response(200, [], file_get_contents(__DIR__ . '/Data/environment.json')));

        $flagsmith = (new Flagsmith('ser.api_key', environmentTtl: 1))
            ->withClient($mockClient);

        // When
        $flagsmith->updateEnvironment();

        // Then
        $this->assertEquals('Test environment', $flagsmith->getLocalEvaluationContext()->environment->name);
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

    public function testGetEnvironmentFlagsAppliesOnlySegmentsNotNeedingAnIdentity()
    {
        // Given
        $flagsmith = (new Flagsmith('ser.api_key', environmentTtl: 1))
            ->withClient(ClientFixtures::getMockClientWithSegmentOverride());
        $flagsmith->updateEnvironment();

        // When
        $environmentFlags = $flagsmith->getEnvironmentFlags();
        $identityFlags = $flagsmith->getIdentityFlags('test-identity', (object) ['plan' => 'premium']);

        // Then
        $environmentFlag = $environmentFlags->getFlag('test_feature');
        $this->assertFalse($environmentFlag->enabled);
        $this->assertEquals('segment-override-value', $environmentFlag->value);

        $environmentPremiumFlag = $environmentFlags->getFlag('premium_feature');
        $this->assertFalse($environmentPremiumFlag->enabled);
        $this->assertEquals('premium-default-value', $environmentPremiumFlag->value);

        $identityPremiumFlag = $identityFlags->getFlag('premium_feature');
        $this->assertTrue($identityPremiumFlag->enabled);
        $this->assertEquals('premium-override-value', $identityPremiumFlag->value);
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

    private function localEvaluationWithExperimentsClient(): ClientInterface
    {
        $handlerBuilder = ClientFixtures::getHandlerBuilder();
        $handlerBuilder->addRoute(
            ClientFixtures::getRouteBuilder()->new()
                ->withMethod('GET')
                ->withPath('/api/v1/environment-document/')
                ->withFileResponse(__DIR__ . '/Data/environment_with_experiments.json')
                ->build()
        );

        return ClientFixtures::getMockClient($handlerBuilder, false);
    }

    public static function localExperimentFlagsProvider(): array
    {
        return [
            'identity served the variant' => ['checkout', 'new checkout', 'treatment', 41],
            'identity served no variant is in control' => ['banner', 'control', 'control', 42],
        ];
    }

    /**
     * @dataProvider localExperimentFlagsProvider
     */
    public function testGetExperimentFlagRecordsTheExposureOfAnIdentityInAnExperimentWithLocalEvaluation(
        string $featureName,
        string $value,
        string $variant,
        int $experimentId
    ) {
        // Given
        $events = [];
        $flagsmith = (new Flagsmith('ser.api_key', environmentTtl: 60))
            ->withClient($this->localEvaluationWithExperimentsClient())
            ->withEventProcessor(new EventProcessor('ser.api_key', client: $this->eventsApiClient($events)));
        $flagsmith->updateEnvironment();

        // When
        $flag = $flagsmith->getExperimentFlag($featureName, 'user_123', (object) ['plan' => 'premium']);
        $flagsmith->flushEvents();

        // Then
        $this->assertEquals($value, $flag->getValue());
        $this->assertEquals($variant, $flag->getVariant());
        $this->assertEquals($experimentId, $flag->getExperiment()->getId());
        $this->assertEquals("{$featureName} experiment", $flag->getExperiment()->getName());
        $this->assertTrue($flag->getExperiment()->getInExperiment());
        $this->assertCount(1, $events);
        $this->assertEquals('$flag_exposure', $events[0]['event']);
        $this->assertEquals($featureName, $events[0]['feature_name']);
        $this->assertEquals('user_123', $events[0]['identifier']);
        $this->assertEquals($variant, $events[0]['value']);
        $this->assertEquals(['plan' => 'premium'], $events[0]['traits']);
        $this->assertEquals($experimentId, $events[0]['metadata']['experiment_id']);
    }

    public function testGetExperimentFlagDoesNotRecordAnExposureOfAnIdentityAnExperimentDoesNotEnrollWithLocalEvaluation()
    {
        // Given
        $events = [];
        $flagsmith = (new Flagsmith('ser.api_key', environmentTtl: 60))
            ->withClient($this->localEvaluationWithExperimentsClient())
            ->withEventProcessor(new EventProcessor('ser.api_key', client: $this->eventsApiClient($events)));
        $flagsmith->updateEnvironment();

        // When
        $flag = $flagsmith->getExperimentFlag('footer', 'user_123');
        $flagsmith->flushEvents();

        // Then
        $this->assertEquals('control', $flag->getValue());
        $this->assertFalse($flag->getExperiment()->getInExperiment());
        $this->assertEmpty($events);
    }

    public function testEnvironmentFlagsCarryNoExperimentWithLocalEvaluation()
    {
        // Given
        $flagsmith = (new Flagsmith('ser.api_key', environmentTtl: 60))
            ->withClient($this->localEvaluationWithExperimentsClient());
        $flagsmith->updateEnvironment();

        // When
        $flag = $flagsmith->getEnvironmentFlags()->getFlag('checkout');

        // Then
        $this->assertEquals('control', $flag->getValue());
        $this->assertNull($flag->getVariant());
        $this->assertNull($flag->getExperiment());
    }

    public function testCloseSendsTheBufferedEventsAndStopsTrackingEvents()
    {
        // Given
        $events = [];
        $flagsmith = (new Flagsmith('api_key'))
            ->withEventProcessor(new EventProcessor('api_key', client: $this->eventsApiClient($events)));
        $flagsmith->trackEvent('checkout_completed', 'user_123');

        // When
        $flagsmith->close(1);
        $flagsmith->trackEvent('signed_up', 'user_123');
        $flagsmith->flushEvents();

        // Then
        $this->assertEquals(['checkout_completed'], array_column($events, 'event'));
    }

    public function testCloseDoesNothingWithoutEvents()
    {
        // Given
        $flagsmith = new Flagsmith('api_key');

        // When / Then
        $flagsmith->close();
        $this->addToAssertionCount(1);
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

    public function testCannotAddEventProcessorInOfflineMode()
    {
        // Given
        $flagsmith = new Flagsmith(offlineMode: true, offlineHandler: new FakeOfflineHandler());
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Cannot use events in offline mode.');

        // When
        $flagsmith->withEventProcessor(new EventProcessor('api_key', client: $this->createMock(ClientInterface::class)));
    }

}
