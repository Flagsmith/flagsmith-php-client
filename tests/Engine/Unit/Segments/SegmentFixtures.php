<?php

namespace FlagsmithTest\Engine\Unit\Segments;

use Flagsmith\Engine\Segments\SegmentConditionModel;
use Flagsmith\Engine\Segments\SegmentConditions;
use Flagsmith\Engine\Segments\SegmentModel;
use Flagsmith\Engine\Segments\SegmentRuleModel;
use Flagsmith\Engine\Segments\SegmentRules;
use Flagsmith\Engine\Utils\Collections\SegmentConditionModelList;
use Flagsmith\Engine\Utils\Collections\SegmentRuleModelList;

class SegmentFixtures
{
    public const TRAIT_KEY_1 = 'email';
    public const TRAIT_VALUE_1 = 'user@example.com';

    public const TRAIT_KEY_2 = 'num_purchase';
    public const TRAIT_VALUE_2 = '12';

    public const TRAIT_KEY_3 = 'date_joined';
    public const TRAIT_VALUE_3 = '2021-01-01';

    private static array $cache = [];

    protected static function conditionTrait1()
    {
        if (empty(self::$cache['conditionTrait1'])) {
            self::$cache['conditionTrait1'] = (new SegmentConditionModel())
                ->withOperator(SegmentConditions::EQUAL)
                ->withProperty(self::TRAIT_KEY_1)
                ->withValue(self::TRAIT_VALUE_1);
        }

        return self::$cache['conditionTrait1'];
    }

    protected static function conditionTrait2()
    {
        if (empty(self::$cache['conditionTrait2'])) {
            self::$cache['conditionTrait2'] = (new SegmentConditionModel())
                ->withOperator(SegmentConditions::EQUAL)
                ->withProperty(self::TRAIT_KEY_2)
                ->withValue(self::TRAIT_VALUE_2);
        }

        return self::$cache['conditionTrait2'];
    }

    protected static function conditionTrait3()
    {
        if (empty(self::$cache['conditionTrait3'])) {
            self::$cache['conditionTrait3'] = (new SegmentConditionModel())
                ->withOperator(SegmentConditions::EQUAL)
                ->withProperty(self::TRAIT_KEY_3)
                ->withValue(self::TRAIT_VALUE_3);
        }

        return self::$cache['conditionTrait3'];
    }

    public static function emptySegment()
    {
        return (new SegmentModel())
            ->withId(1)
            ->withName('empty_segment');
    }

    public static function segmentSingleCondition()
    {
        return (new SegmentModel())
            ->withId(2)
            ->withName('segment_one_condition')
            ->withRules(
                new SegmentRuleModelList([
                    (new SegmentRuleModel())
                        ->withType(SegmentRules::ALL_RULE)
                        ->withConditions(
                            new SegmentConditionModelList([
                                self::conditionTrait1()
                            ])
                        )
                ])
            );
    }

    public static function segmentMultipleConditionsAll()
    {
        return (new SegmentModel())
            ->withId(3)
            ->withName('segment_multiple_conditions_all')
            ->withRules(
                new SegmentRuleModelList([
                    (new SegmentRuleModel())->withType(SegmentRules::ALL_RULE)
                        ->withConditions(
                            new SegmentConditionModelList([
                                self::conditionTrait1(),
                                self::conditionTrait2(),
                            ])
                        )
                ])
            );
    }

    public static function segmentMultipleConditionsAny()
    {
        return (new SegmentModel())
            ->withId(4)
            ->withName('segment_multiple_conditions_any')
            ->withRules(
                new SegmentRuleModelList([
                    (new SegmentRuleModel())->withType(SegmentRules::ANY_RULE)
                        ->withConditions(
                            new SegmentConditionModelList([
                                self::conditionTrait1(),
                                self::conditionTrait2(),
                            ])
                        )
                ])
            );
    }

    public static function segmentNestedRules()
    {
        return (new SegmentModel())
            ->withId(5)
            ->withName('segment_nested_rules_all')
            ->withRules(
                new SegmentRuleModelList([
                    (new SegmentRuleModel())
                        ->withType(SegmentRules::ALL_RULE)
                        ->withRules(
                            new SegmentRuleModelList([
                                (new SegmentRuleModel())
                                    ->withType(SegmentRules::ALL_RULE)
                                    ->withConditions(
                                        new SegmentConditionModelList([
                                            self::conditionTrait1(),
                                            self::conditionTrait2(),
                                        ])
                                    ),
                                (new SegmentRuleModel())
                                    ->withType(SegmentRules::ALL_RULE)
                                    ->withConditions(
                                        new SegmentConditionModelList([
                                            self::conditionTrait3()
                                        ])
                                    )
                            ])
                        )
                ])
            );
    }

    public static function segmentConditionsAndNestedRules()
    {
        return (new SegmentModel())
            ->withId(6)
            ->withName('segment_multiple_conditions_all_and_nested_rules')
            ->withRules(
                new SegmentRuleModelList([
                    (new SegmentRuleModel())
                        ->withType(SegmentRules::ALL_RULE)
                        ->withConditions(
                            new SegmentConditionModelList([
                                self::conditionTrait1()
                            ])
                        )
                        ->withRules(
                            new SegmentRuleModelList([
                                (new SegmentRuleModel())
                                    ->withType(SegmentRules::ALL_RULE)
                                    ->withConditions(
                                        new SegmentConditionModelList([
                                            self::conditionTrait2()
                                        ])
                                    ),
                                (new SegmentRuleModel())
                                    ->withType(SegmentRules::ALL_RULE)
                                    ->withConditions(
                                        new SegmentConditionModelList([
                                            self::conditionTrait3()
                                        ])
                                    )
                            ])
                        )
                ])
            );
    }

    public static function segmentToCheckIfFooIsSet()
    {
        return (new SegmentModel())
            -> withId(7)
            -> withName('segment_to_check_if_foo_is_set')
            -> withRules(
                new SegmentRuleModelList([
                    (new SegmentRuleModel())
                        -> withType(SegmentRules::ALL_RULE)
                        -> withConditions(
                            new SegmentConditionModelList([
                                (new SegmentConditionModel())
                                    -> withValue(null)
                                    -> withProperty('foo')
                                    -> withOperator(SegmentConditions::IS_SET)
                            ])
                        )
                ])
            );
    }

    public static function segmentToCheckIfFooIsNotSet()
    {
        return (new SegmentModel())
            -> withId(7)
            -> withName('segment_to_check_if_foo_is_not_set')
            -> withRules(
                new SegmentRuleModelList([
                    (new SegmentRuleModel())
                        -> withType(SegmentRules::ALL_RULE)
                        -> withConditions(
                            new SegmentConditionModelList([
                                (new SegmentConditionModel())
                                    -> withValue(null)
                                    -> withProperty('foo')
                                    -> withOperator(SegmentConditions::IS_NOT_SET)
                            ])
                        )
                ])
            );
    }
}
