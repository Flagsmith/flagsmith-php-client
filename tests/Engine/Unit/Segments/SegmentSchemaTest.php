<?php

use Flagsmith\Engine\Features\FeatureStateModel;
use Flagsmith\Engine\Segments\SegmentConditionModel;
use Flagsmith\Engine\Segments\SegmentConditions;
use Flagsmith\Engine\Segments\SegmentModel;
use Flagsmith\Engine\Segments\SegmentRuleModel;
use Flagsmith\Engine\Segments\SegmentRules;
use Flagsmith\Engine\Utils\Collections\FeatureStateModelList;
use Flagsmith\Engine\Utils\Collections\SegmentConditionModelList;
use Flagsmith\Engine\Utils\Collections\SegmentRuleModelList;
use PHPUnit\Framework\TestCase;
use Flagsmith\Engine\Features\FeatureModel;
use Flagsmith\Engine\Features\FeatureTypes;

class SegmentSchemaTest extends TestCase
{
    public function testSegmentSchemaEngineModelObjectToDict()
    {
        $segmentModel = (new SegmentModel())
            ->withId(1)
            ->withName('Segment')
            ->withRules(
                new SegmentRuleModelList([
                    (new SegmentRuleModel())
                    ->withType(SegmentRules::ALL_RULE)
                    ->withConditions(
                        new SegmentConditionModelList([
                            (new SegmentConditionModel())
                            ->withOperator(SegmentConditions::EQUAL)
                            ->withProperty('foo')
                            ->withValue('bar')
                        ])
                    )
                ])
            )
            ->withFeatureStates(
                new FeatureStateModelList([
                    (new FeatureStateModel())
                        ->withDjangoId(1)
                        ->withEnabled(true)
                        ->withFeature(
                            (new FeatureModel())
                                ->withId(1)
                                ->withName('my_feature')
                                ->withType(FeatureTypes::STANDARD)
                        )
                ])
            );

        $segmentDict = json_decode(json_encode($segmentModel));
        $this->assertEquals(count($segmentDict->feature_states), 1);
        $this->assertEquals(count($segmentDict->rules), 1);
    }

    public function testDictToSegmentModel()
    {
        $segmentDict = (object) [
            'id' => 1,
            'name' => 'Segment',
            'rules' => [
                (object) [
                    'rules' => [],
                    'conditions' => [
                        (object) ['operator' => 'EQUAL', 'property_' => 'foo', 'value' => 'bar']
                    ],
                    'type' => 'ALL',
                ]
            ],
            'feature_states' => [
                (object) [
                    'multivariate_feature_state_values' => [],
                    'id' => 1,
                    'enabled' => true,
                    'feature_state_value' => null,
                    'feature' => (object) ['id' => 1, 'name' => 'my_feature', 'type' => 'STANDARD'],
                ]
            ],
        ];

        $segmentModel = SegmentModel::build($segmentDict);

        $this->assertTrue($segmentModel instanceof SegmentModel);
        $this->assertEquals($segmentModel->getId(), $segmentDict->id);
    }

    public function testSegmentConditionSchemaLoadWhenPropertyIsNone()
    {
        $segmentConditionDict = (object) [
            'operator' => 'PERCENTAGE_SPLIT',
            'value' => 10,
            'property_' => null
        ];

        $segmentConditionModel = SegmentConditionModel::build($segmentConditionDict);

        $this->assertEquals($segmentConditionModel->getValue(), $segmentConditionDict->value);
        $this->assertEquals($segmentConditionModel->getOperator(), $segmentConditionDict->operator);
        $this->assertNull($segmentConditionModel->getProperty());
    }
}
