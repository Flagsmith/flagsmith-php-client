<?php

use Flagsmith\Engine\Features\FeatureStateModel;
use Flagsmith\Engine\Identities\IdentityModel;
use Flagsmith\Engine\Identities\Traits\TraitModel;
use Flagsmith\Engine\Segments\SegmentConditionModel;
use Flagsmith\Engine\Segments\SegmentConditions;
use Flagsmith\Engine\Segments\SegmentEvaluator;
use Flagsmith\Engine\Segments\SegmentModel;
use Flagsmith\Engine\Segments\SegmentRuleModel;
use Flagsmith\Engine\Segments\SegmentRules;
use Flagsmith\Engine\Utils\Collections\IdentityTraitList;
use FlagsmithTest\Engine\Unit\Segments\SegmentFixtures;
use PHPUnit\Framework\TestCase;
use Flagsmith\Engine\Utils\Collections\SegmentConditionModelList;
use Flagsmith\Engine\Utils\Collections\SegmentRuleModelList;
use Flagsmith\Engine\Utils\Hashing;
use FlagsmithTest\Engine\Fixtures;

class SegmentEvaluatorTest extends TestCase
{
    public function segmentIdentityTraitsExpectedResult()
    {
        return [
            [SegmentFixtures::emptySegment(), [], false],
            [SegmentFixtures::segmentSingleCondition(), [], false],
            [
                SegmentFixtures::segmentSingleCondition(),
                [(new TraitModel())
                    ->withTraitKey(SegmentFixtures::TRAIT_KEY_1)
                    ->withTraitValue(SegmentFixtures::TRAIT_VALUE_1)],
                true
            ],
            [SegmentFixtures::segmentMultipleConditionsAll(), [], false],
            [
                SegmentFixtures::segmentMultipleConditionsAll(),
                [(new TraitModel())
                    ->withTraitKey(SegmentFixtures::TRAIT_KEY_1)
                    ->withTraitValue(SegmentFixtures::TRAIT_VALUE_1)],
                false
            ],
            [
                SegmentFixtures::segmentMultipleConditionsAll(),
                [(new TraitModel())
                    ->withTraitKey(SegmentFixtures::TRAIT_KEY_1)
                    ->withTraitValue(SegmentFixtures::TRAIT_VALUE_1),
                    (new TraitModel())
                        ->withTraitKey(SegmentFixtures::TRAIT_KEY_2)
                        ->withTraitValue(SegmentFixtures::TRAIT_VALUE_2)],
                true
            ],
            [SegmentFixtures::segmentMultipleConditionsAny(), [], false],
            [
                SegmentFixtures::segmentMultipleConditionsAny(),
                [(new TraitModel())
                    ->withTraitKey(SegmentFixtures::TRAIT_KEY_1)
                    ->withTraitValue(SegmentFixtures::TRAIT_VALUE_1)],
                true
            ],
            [
                SegmentFixtures::segmentMultipleConditionsAny(),
                [(new TraitModel())
                    ->withTraitKey(SegmentFixtures::TRAIT_KEY_2)
                    ->withTraitValue(SegmentFixtures::TRAIT_VALUE_2)],
                true
            ],
            [
                SegmentFixtures::segmentMultipleConditionsAny(),
                [(new TraitModel())
                    ->withTraitKey(SegmentFixtures::TRAIT_KEY_1)
                    ->withTraitValue(SegmentFixtures::TRAIT_VALUE_1),
                    (new TraitModel())
                        ->withTraitKey(SegmentFixtures::TRAIT_KEY_2)
                        ->withTraitValue(SegmentFixtures::TRAIT_VALUE_2)],
                true
            ],
            [SegmentFixtures::segmentNestedRules(), [], false],
            [
                SegmentFixtures::segmentNestedRules(),
                [(new TraitModel())
                    ->withTraitKey(SegmentFixtures::TRAIT_KEY_1)
                    ->withTraitValue(SegmentFixtures::TRAIT_VALUE_1)],
                false
            ],
            [
                SegmentFixtures::segmentNestedRules(),
                [(new TraitModel())
                    ->withTraitKey(SegmentFixtures::TRAIT_KEY_1)
                    ->withTraitValue(SegmentFixtures::TRAIT_VALUE_1),
                    (new TraitModel())
                        ->withTraitKey(SegmentFixtures::TRAIT_KEY_2)
                        ->withTraitValue(SegmentFixtures::TRAIT_VALUE_2),
                    (new TraitModel())
                        ->withTraitKey(SegmentFixtures::TRAIT_KEY_3)
                        ->withTraitValue(SegmentFixtures::TRAIT_VALUE_3)],
                true
            ],
            [SegmentFixtures::segmentConditionsAndNestedRules(), [], false],
            [
                SegmentFixtures::segmentConditionsAndNestedRules(),
                [(new TraitModel())
                    ->withTraitKey(SegmentFixtures::TRAIT_KEY_1)
                    ->withTraitValue(SegmentFixtures::TRAIT_VALUE_1)],
                false
            ],
            [
                SegmentFixtures::segmentConditionsAndNestedRules(),
                [(new TraitModel())
                    ->withTraitKey(SegmentFixtures::TRAIT_KEY_1)
                    ->withTraitValue(SegmentFixtures::TRAIT_VALUE_1),
                    (new TraitModel())
                        ->withTraitKey(SegmentFixtures::TRAIT_KEY_2)
                        ->withTraitValue(SegmentFixtures::TRAIT_VALUE_2),
                    (new TraitModel())
                        ->withTraitKey(SegmentFixtures::TRAIT_KEY_3)
                        ->withTraitValue(SegmentFixtures::TRAIT_VALUE_3)],
                true
            ],
            [
                SegmentFixtures::segmentToCheckIfFooIsSet(),
                [(new TraitModel())
                    -> withTraitKey('foo')
                    -> withTraitValue('bar')],
                true
            ],
            [
                SegmentFixtures::segmentToCheckIfFooIsSet(),
                [],
                false
            ],
            [
                SegmentFixtures::segmentToCheckIfFooIsSet(),
                [(new TraitModel())
                    -> withTraitKey('notfoo')
                    -> withTraitValue('bar')],
                false
            ],
            [
                SegmentFixtures::segmentToCheckIfFooIsNotSet(),
                [(new TraitModel())
                    -> withTraitKey('foo')
                    -> withTraitValue('bar')],
                false
            ],
            [
                SegmentFixtures::segmentToCheckIfFooIsNotSet(),
                [],
                true
            ],
            [
                SegmentFixtures::segmentToCheckIfFooIsNotSet(),
                [(new TraitModel())
                    -> withTraitKey('notfoo')
                    -> withTraitValue('bar')],
                true
            ]
        ];
    }

    /**
     * @dataProvider segmentIdentityTraitsExpectedResult
     */
    public function testIdentityInSegment($segment, $identityTraits, $expectedResult)
    {
        $identity = (new IdentityModel())
            ->withIdentifier('foo')
            ->withIdentityTraits(
                new IdentityTraitList($identityTraits)
            )
            ->withEnvironmentApiKey('api-key');

        $this->assertEquals(
            SegmentEvaluator::evaluateIdentityInSegment($identity, $segment),
            $expectedResult
        );
    }

    public function segmentSplitValues()
    {
        return [
            [10, 1, true], [100, 50, true], [0, 1, false], [10, 20, false]
        ];
    }


    /**
     * @dataProvider segmentSplitValues
     */
    public function testIdentityInSegmentPercentageSplit($segmentSplitValue, $identityHashedPercentage, $expectedResult)
    {
        $this->assertTrue(true);
        $percentageSplitCondition = (new SegmentConditionModel())
            ->withOperator(SegmentConditions::PERCENTAGE_SPLIT)
            ->withValue("{$segmentSplitValue}");

        $segmentRule = (new SegmentRuleModel())
            ->withType(SegmentRules::ALL_RULE)
            ->withConditions(
                new SegmentConditionModelList([$percentageSplitCondition])
            );

        $segmentModel = (new SegmentModel())
            ->withId(1)
            ->withName('splitty')
            ->withRules(
                new SegmentRuleModelList([$segmentRule])
            );

        $hashingStub = $this->createMock(Hashing::class);
        $hashingStub
            ->method('getHashedPercentageForObjectIds')
            ->will($this->returnValue($identityHashedPercentage));
        SegmentEvaluator::setHashObject($hashingStub);

        $result = SegmentEvaluator::evaluateIdentityInSegment(Fixtures::identity(), $segmentModel);

        $this->assertEquals($result, $expectedResult);
    }
}
