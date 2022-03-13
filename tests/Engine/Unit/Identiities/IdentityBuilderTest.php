<?php

use Flagsmith\Engine\Features\FeatureStateModel;
use Flagsmith\Engine\Features\FeatureTypes;
use Flagsmith\Engine\Identities\IdentityModel;
use Flagsmith\Engine\Utils\Collections\IdentityFeaturesList;
use PHPUnit\Framework\TestCase;

class IdentityBuilderTest extends TestCase
{
    public function testBuildIdentityModelFromDictionaryNoFeatureStates()
    {
        $identityDict = [
            'id' => 1,
            'identifier' => 'test-identity',
            'environment_api_key' => 'api-key',
            'created_date' => '2021-08-22T06:25:23.406995Z',
            'identity_traits' => [
                ['trait_key' => 'trait_key', 'trait_value' => 'trait_value']
            ],
        ];

        $identity = IdentityModel::build($identityDict);
        $this->assertTrue($identity instanceof IdentityModel);
        $identityFeatures = $identity->getIdentityFeatures()->getArrayCopy();
        $this->assertEquals(count($identityFeatures), 0);
        $identityTraits = $identity->getIdentityTraits()->getArrayCopy();
        $this->assertEquals(count($identityTraits), 1);
    }

    public function testBuildIdentityModelFromDictionaryUsesIdentityFeatureListForIdentityFeatures()
    {
        $identityDict = (object) [
            'id' => 1,
            'identifier' => 'test-identity',
            'environment_api_key' => 'api-key',
            'created_date' => '2021-08-22T06:25:23.406995Z',
            'identity_features' => [
                (object) [
                    'id' => 1,
                    'feature' => (object) [
                        'id' => 1,
                        'name' => 'test_feature',
                        'type' => FeatureTypes::STANDARD
                    ],
                    'enabled' => true,
                    'feature_state_value' => 'some-value'
                ]
            ],
        ];

        $identity = IdentityModel::build($identityDict);
        $this->assertTrue($identity->getIdentityFeatures() instanceof IdentityFeaturesList);
    }

    public function testBuildBuildIdentityModelFromDictCreatesIdentityUuid()
    {
        $identity = IdentityModel::build(['identifier' => 'test_user', 'environment_api_key' => 'some_key']);
        $this->assertNotNull($identity->getIdentityUuid());
    }

    public function testBuildIdentityModelFromDictionaryWithFeatureStates()
    {
        $identityDict = (object) [
            'id' => 1,
            'identifier' => 'test-identity',
            'environment_api_key' => 'api-key',
            'created_date' => '2021-08-22T06:25:23.406995Z',
            'identity_features' => [
                (object) [
                    'id' => 1,
                    'feature' => (object) [
                        'id' => 1,
                        'name' => 'test_feature',
                        'type' => FeatureTypes::STANDARD,
                    ],
                    'enabled' => true,
                    'feature_state_value' => 'some-value',
                ]
            ],
        ];

        $identity = IdentityModel::build($identityDict);
        $this->assertTrue($identity instanceof IdentityModel);
        $identityFeatures = $identity->getIdentityFeatures()->getArrayCopy();
        $this->assertEquals(count($identityFeatures), 1);
        $this->assertTrue($identityFeatures[0] instanceof FeatureStateModel);
    }

    public function testIdentityDictCreatedUsingModelCanConvertBackToModel()
    {
        $identity = (new IdentityModel())
            ->withEnvironmentApiKey('some_key')
            ->withIdentifier('test_identifier');

        $identityDict = json_decode(json_encode($identity));

        $identity = IdentityModel::build($identityDict);
        $this->assertTrue($identity instanceof IdentityModel);
        $this->assertEquals($identity->getEnvironmentApiKey(), 'some_key');
    }
}
