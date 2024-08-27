<?php

use Flagsmith\Exceptions\FlagsmithAPIError;
use Flagsmith\Flagsmith;
use Flagsmith\Models\DefaultFlag;
use FlagsmithTest\ClientFixtures;
use FlagsmithTest\Offline\FakeOfflineHandler;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamFactoryInterface;

class FlagsmithClientTest extends TestCase
{
    public function testUpdateEnvironmentSetsEnvironment()
    {
        $flagsmith = (new Flagsmith('ser.abcdefg', Flagsmith::DEFAULT_API_URL, null, 10))
            ->withClient(ClientFixtures::getMockClient());

        $flagsmith->updateEnvironment();

        $this->assertNotNull($flagsmith->getEnvironment());
        $this->assertEquals($flagsmith->getEnvironment(), ClientFixtures::getEnvironmentModel());
    }

    public function testGetEnvironmentFlagsCallsApiWhenNoLocalEnvironment()
    {
        $flagsmith = (new Flagsmith('api_key'))
            ->withClient(ClientFixtures::getMockClient());

        $allFlags = $flagsmith->getEnvironmentFlags()->allFlags();

        $this->assertTrue($allFlags[0]->enabled);
        $this->assertEquals($allFlags[0]->value, 'some-value');
        $this->assertEquals($allFlags[0]->feature_name, 'some_feature');
    }

    public function testGetEnvironmentFlagsUsesLocalEnvironmentWhenAvailable()
    {
        $flagsmith = (new Flagsmith('api_key'))
            ->withClient(ClientFixtures::getMockClient());

        $flagsmith->updateEnvironment();

        $allFlags = $flagsmith->getEnvironmentFlags()->allFlags();
        $environmentModel = ClientFixtures::getEnvironmentModel();
        $firstFeatureState = $environmentModel->getFeatureStates()[0];

        $this->assertEquals($allFlags[0]->feature_name, $firstFeatureState->getFeature()->getName());
        $this->assertEquals($allFlags[0]->enabled, $firstFeatureState->getEnabled());
        $this->assertEquals($allFlags[0]->value, $firstFeatureState->getValue());
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
            $identifier = 'identifier';
            $traits = (object)['foo' => 'bar'];

            $segments = $flagsmith->getIdentitySegments($identifier, $traits);
            $this->assertEquals(count($segments), 1);
            $this->assertEquals($segments[0]->getName(), 'Test segment');
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

    public function testOfflineMode() {
        // Given
        $flagsmith = (new Flagsmith(offlineMode:true, offlineHandler:new FakeOfflineHandler()));

        // When
        $environmentFlags = $flagsmith->getEnvironmentFlags();
        $identityFlags = $flagsmith->getIdentityFlags("my-identity");

        // Then
        $this->assertEquals($environmentFlags->getFlag("some_feature")->enabled, true);
        $this->assertEquals($environmentFlags->getFlag("some_feature")->value, "some-value");

        $this->assertEquals($identityFlags->getFlag("some_feature")->enabled, true);
        $this->assertEquals($identityFlags->getFlag("some_feature")->value, "some-value");
    }

    public function testFlagsmithUseOfflineHandlerIfSetAndNoApiResponse() {
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

        $flagsmith = (new Flagsmith(apiKey:"some-key", offlineHandler: new FakeOfflineHandler()))
            ->withClient(ClientFixtures::getMockClient($handlerBuilder, false));

        // When
        $environmentFlags = $flagsmith->getEnvironmentFlags();
        $identityFlags = $flagsmith->getIdentityFlags("my-identity");

        // Then
        $this->assertEquals($environmentFlags->getFlag("some_feature")->enabled, true);
        $this->assertEquals($environmentFlags->getFlag("some_feature")->value, "some-value");

        $this->assertEquals($identityFlags->getFlag("some_feature")->enabled, true);
        $this->assertEquals($identityFlags->getFlag("some_feature")->value, "some-value");
    }

    public function testCannotUseOfflineModeWithoutOfflineHandler() {
        // Given
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('offlineHandler must be provided to use offline mode.');

        // When
        new Flagsmith(offlineMode:true, offlineHandler:null);
    }

    public function testCannotUseDefaultHandlerAndOfflineHandler() {
        // Given
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('Cannot use both defaultFlagHandler and offlineHandler.');

        // When
        new Flagsmith(offlineHandler:new FakeOfflineHandler());
    }

    public function testCannotCreateFlagsmithClientInRemoteEvaluationWithoutApiKey() {
        // Given
        $this->expectException(ValueError::class);
        $this->expectExceptionMessage('environmentKey is required');

        // When
        new Flagsmith();
    }

    public function testOfflineHandlerUsedAsFallbackForLocalEvaluation() {
        // Given
        $handlerBuilder = ClientFixtures::getHandlerBuilder();
        $handlerBuilder->addRoute(
            ClientFixtures::getRouteBuilder()->new()
            ->withMethod('GET')
            ->withPath('/api/v1/environment-document/')
            ->withResponse(new Response(500))
            ->build()
        );

        $flagsmith = (new Flagsmith(apiKey:"ser.some-key", environmentTtl:3, offlineHandler:new FakeOfflineHandler()));

        // When
        $environmentFlags = $flagsmith->getEnvironmentFlags();
        $identityFlags = $flagsmith->getIdentityFlags("my-identity");

        // Then
        $this->assertEquals($environmentFlags->getFlag("some_feature")->enabled, true);
        $this->assertEquals($environmentFlags->getFlag("some_feature")->value, "some-value");

        $this->assertEquals($identityFlags->getFlag("some_feature")->enabled, true);
        $this->assertEquals($identityFlags->getFlag("some_feature")->value, "some-value");
    }
}
