<?php

use Flagsmith\Engine\Environments\EnvironmentAPIKeyModel;
use Flagsmith\Engine\Environments\EnvironmentModel;
use Flagsmith\Engine\Features\FeatureStateModel;
use Flagsmith\Engine\Features\FeatureTypes;
use Flagsmith\Engine\Features\MultivariateFeatureStateValueModel;
use PHPUnit\Framework\TestCase;

class EnvironmentBuilderTest extends TestCase
{
    public function test_get_flags_for_environment_returns_feature_states_for_environment_dictionary()
    {
        $stringValue = 'foo';
        $featureWithStringValueName = 'feature_with_string_value';

        $environmentDict = (object) [
            'id' => 1,
            'api_key' => 'api-key',
            'project' => (object) [
                'id' => 1,
                'name' => 'test project',
                'organisation' => (object) [
                    'id' => 1,
                    'name' => 'Test Org',
                    'stop_serving_flags' => false,
                    'persist_trait_data' => true,
                    'feature_analytics' => true,
                ],
                'hide_disabled_flags' => false,
            ],
            'feature_states' => [
                (object) [
                    'id' => 1,
                    'enabled' => true,
                    'feature_state_value' => null,
                    'feature' => (object) [
                        'id' => 1,
                        'name' => 'enabled_feature',
                        'type' => FeatureTypes::STANDARD
                    ],
                ],
                (object) [
                    'id' => 2,
                    'enabled' => false,
                    'feature_state_value' => null,
                    'feature' => (object) [
                        'id' => 2,
                        'name' => 'disabled_feature',
                        'type' => FeatureTypes::STANDARD
                    ],
                ],
                (object) [
                    'id' => 3,
                    'enabled' => true,
                    'feature_state_value' => $stringValue,
                    'feature' => (object) [
                        'id' => 3,
                        'name' => $featureWithStringValueName,
                        'type' => FeatureTypes::STANDARD,
                    ],
                ],
            ],
        ];

        $environment = EnvironmentModel::build($environmentDict);

        $this->assertTrue($environment instanceof EnvironmentModel);

        $featureStates = $environment->getFeatureStates();

        $this->assertEquals(count($featureStates), 3);
        foreach ($featureStates as $featureState) {
            $this->assertTrue($featureState instanceof FeatureStateModel);
        }

        $this->assertEquals(
            $featureStates
                ->getFeatureStateModel($featureWithStringValueName)
                ->getValue(),
            $stringValue
        );
    }

    public function testBuildEnvironmentModelWithMultivariateFlag()
    {
        $variate1Value = 'value-1';
        $variate2Value = 'value-2';
        $environmentDict = (object) [
            'id' => 1,
            'api_key' => 'api-key',
            'project' => (object) [
                'id' => 1,
                'name' => 'test project',
                'organisation' => (object) [
                    'id' => 1,
                    'name' => 'Test Org',
                    'stop_serving_flags' => false,
                    'persist_trait_data' => true,
                    'feature_analytics' => true,
                ],
                'hide_disabled_flags' => false,
            ],
            'feature_states' => [
                (object) [
                    'id' => 1,
                    'enabled' => true,
                    'feature_state_value' => null,
                    'feature' => (object) [
                        'id' => 1,
                        'name' => 'enabled_feature',
                        'type' => FeatureTypes::STANDARD,
                    ],
                    'multivariate_feature_state_values' => [
                        (object) [
                            'id' => 1,
                            'percentage_allocation' => 10.0,
                            'multivariate_feature_option' => [
                                'value' => $variate1Value,
                            ],
                        ],
                        (object) [
                            'id' => 2,
                            'percentage_allocation' => 10.0,
                            'multivariate_feature_option' => [
                                'value' => $variate2Value,
                                'id' => 2,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $environment = EnvironmentModel::build($environmentDict);

        $this->assertTrue($environment instanceof EnvironmentModel);

        $featureStates = $environment->getFeatureStates()->getArrayCopy();
        $this->assertEquals(count($featureStates), 1);

        $featureState = $featureStates[0];
        $mvFeatureStateValues = $featureState
            ->getMultivariateFeatureStateValues()
            ->getArrayCopy();

        $this->assertEquals(count($mvFeatureStateValues), 2);

        foreach ($mvFeatureStateValues as $featureValue) {
            $this->assertTrue($featureValue instanceof MultivariateFeatureStateValueModel);
        }
    }

    public function testBuildEnvironmentApiKeyModel()
    {
        $environmentDict = (object) [
            'key' => 'ser.7duQYrsasJXqdGsdaagyfU',
            'active' => true,
            'created_at' => '2022-02-07T04:58:25.969438+00:00',
            'client_api_key' => 'RQchaCQ2mYicSCAwKoAg2E',
            'id' => 10,
            'name' => 'api key 2',
            'expires_at' => null,
        ];

        $environment = EnvironmentAPIKeyModel::build($environmentDict);
        $this->assertEquals($environment->getKey(), $environmentDict->key);
    }
}
