<?php

use Flagsmith\Engine\Segments\SegmentConditionModel;
use Flagsmith\Engine\Segments\SegmentConditions;
use Flagsmith\Engine\Segments\SegmentEvaluator;
use Flagsmith\Engine\Segments\SegmentRules;
use PHPUnit\Framework\TestCase;

class SegmentModelsTest extends TestCase
{
    public function conditionParametersTraitValues()
    {
        return [
            [SegmentConditions::EQUAL, 'bar', 'bar', true],
            [SegmentConditions::EQUAL, 'bar', 'baz', false],
            [SegmentConditions::EQUAL, 1, '1', true],
            [SegmentConditions::EQUAL, 1, '2', false],
            [SegmentConditions::EQUAL, true, 'true', true],
            [SegmentConditions::EQUAL, false, 'false', true],
            [SegmentConditions::EQUAL, false, 'true', false],
            [SegmentConditions::EQUAL, true, 'false', false],
            [SegmentConditions::EQUAL, 1.23, '1.23', true],
            [SegmentConditions::EQUAL, 1.23, '4.56', false],
            [SegmentConditions::GREATER_THAN, 2, '1', true],
            [SegmentConditions::GREATER_THAN, 1, '1', false],
            [SegmentConditions::GREATER_THAN, 0, '1', false],
            [SegmentConditions::GREATER_THAN, 2.1, '2.0', true],
            [SegmentConditions::GREATER_THAN, 2.1, '2.1', false],
            [SegmentConditions::GREATER_THAN, 2.0, '2.1', false],
            [SegmentConditions::GREATER_THAN_INCLUSIVE, 2, '1', true],
            [SegmentConditions::GREATER_THAN_INCLUSIVE, 1, '1', true],
            [SegmentConditions::GREATER_THAN_INCLUSIVE, 0, '1', false],
            [SegmentConditions::GREATER_THAN_INCLUSIVE, 2.1, '2.0', true],
            [SegmentConditions::GREATER_THAN_INCLUSIVE, 2.1, '2.1', true],
            [SegmentConditions::GREATER_THAN_INCLUSIVE, 2.0, '2.1', false],
            [SegmentConditions::LESS_THAN, 1, '2', true],
            [SegmentConditions::LESS_THAN, 1, '1', false],
            [SegmentConditions::LESS_THAN, 1, '0', false],
            [SegmentConditions::LESS_THAN, 2.0, '2.1', true],
            [SegmentConditions::LESS_THAN, 2.1, '2.1', false],
            [SegmentConditions::LESS_THAN, 2.1, '2.0', false],
            [SegmentConditions::LESS_THAN_INCLUSIVE, 1, '2', true],
            [SegmentConditions::LESS_THAN_INCLUSIVE, 1, '1', true],
            [SegmentConditions::LESS_THAN_INCLUSIVE, 1, '0', false],
            [SegmentConditions::LESS_THAN_INCLUSIVE, 2.0, '2.1', true],
            [SegmentConditions::LESS_THAN_INCLUSIVE, 2.1, '2.1', true],
            [SegmentConditions::LESS_THAN_INCLUSIVE, 2.1, '2.0', false],
            [SegmentConditions::NOT_EQUAL, 'bar', 'baz', true],
            [SegmentConditions::NOT_EQUAL, 'bar', 'bar', false],
            [SegmentConditions::NOT_EQUAL, 1, '2', true],
            [SegmentConditions::NOT_EQUAL, 1, '1', false],
            [SegmentConditions::NOT_EQUAL, true, 'false', true],
            [SegmentConditions::NOT_EQUAL, false, 'true', true],
            [SegmentConditions::NOT_EQUAL, false, 'false', false],
            [SegmentConditions::NOT_EQUAL, true, 'true', false],
            [SegmentConditions::CONTAINS, 'bar', 'b', true],
            [SegmentConditions::CONTAINS, 'bar', 'bar', true],
            [SegmentConditions::CONTAINS, 'bar', 'baz', false],
            [SegmentConditions::NOT_CONTAINS, 'bar', 'b', false],
            [SegmentConditions::NOT_CONTAINS, 'bar', 'bar', false],
            [SegmentConditions::NOT_CONTAINS, 'bar', 'baz', true],
            [SegmentConditions::IN, 'foo', '', false],
            [SegmentConditions::IN, 'ba', 'foo,bar', false],
            [SegmentConditions::IN, 'foo', 'foo,bar', true],
            [SegmentConditions::IN, 'bar', 'foo,bar', true],
            [SegmentConditions::IN, 'foo', 'foo', true],
            [SegmentConditions::IN, 1, '1,2,3,4', true],
            [SegmentConditions::IN, 1, '', false],
            [SegmentConditions::IN, 1, '1', true],
            // Flagsmith's engine does not evaluate `IN` condition for floats/doubles and booleans
            // due to ambiguous serialization across supported platforms.
            [SegmentConditions::IN, 1.5, '1.5', false],
            [SegmentConditions::IN, false, 'false', false],
            [SegmentConditions::REGEX, 'foo', '[a-z]+', true],
            [SegmentConditions::REGEX, 'FOO', '[a-z]+', false],
            [SegmentConditions::REGEX, '1.2.3', '\\d', true],
            [SegmentConditions::REGEX, 123, '^\\d*[13579]$', true],
            [SegmentConditions::REGEX, 122, '^\\d*[13579]$', false],
            [SegmentConditions::MODULO, 2, '2|0', true],
            [SegmentConditions::MODULO, 2.0, '2|0', true],
            [SegmentConditions::MODULO, 2.0, '2.0|0', true],
            [SegmentConditions::MODULO, 3, '2|0', false],
            [SegmentConditions::MODULO, 'foo', '2|0', false],
            [SegmentConditions::MODULO, 'foo', 'foo|bar', false],
            [SegmentConditions::MODULO, 2, 'foo|bar', false],
        ];
    }

    /**
     * @dataProvider conditionParametersTraitValues
     */
    public function testSegmentConditionMatchesTraitValue($operator, $traitValue, $conditionValue, $expectedResult)
    {
        $segmentCondition = (new SegmentConditionModel())
            ->withOperator($operator)
            ->withProperty('foo')
            ->withValue($conditionValue);

        $this->assertEquals(
            $expectedResult,
            $segmentCondition->matchesTraitValue($traitValue)
        );
    }
    public function conditionParametersTraitVersionValues()
    {
        return [
            [SegmentConditions::EQUAL, '1.0.0', '1.0.0:semver', true],
            [SegmentConditions::EQUAL, '1.0.0', '1.0.1:semver', false],
            [SegmentConditions::NOT_EQUAL, '1.0.0', '1.0.0:semver', false],
            [SegmentConditions::NOT_EQUAL, '1.0.0', '1.0.1:semver', true],
            [SegmentConditions::GREATER_THAN, '1.0.1', '1.0.0:semver', true],
            [SegmentConditions::GREATER_THAN, '1.0.0', '1.0.0-beta:semver', true],
            [SegmentConditions::GREATER_THAN, '1.0.1', '1.2.0:semver', false],
            [SegmentConditions::GREATER_THAN, '1.0.1', '1.0.1:semver', false],
            [SegmentConditions::GREATER_THAN, '1.2.4', '1.2.3-pre.2+build.4:semver', true],
            [SegmentConditions::LESS_THAN, '1.0.0', '1.0.1:semver', true],
            [SegmentConditions::LESS_THAN, '1.0.0', '1.0.0:semver', false],
            [SegmentConditions::LESS_THAN, '1.0.1', '1.0.0:semver', false],
            [SegmentConditions::LESS_THAN, '1.0.0-rc.2', '1.0.0-rc.3:semver', true],
            [SegmentConditions::GREATER_THAN_INCLUSIVE, '1.0.1', '1.0.0:semver', true],
            [SegmentConditions::GREATER_THAN_INCLUSIVE, '1.0.1', '1.2.0:semver', false],
            [SegmentConditions::GREATER_THAN_INCLUSIVE, '1.0.1', '1.0.1:semver', true],
            [SegmentConditions::LESS_THAN_INCLUSIVE, '1.0.0', '1.0.1:semver', true],
            [SegmentConditions::LESS_THAN_INCLUSIVE, '1.0.0', '1.0.0:semver', true],
            [SegmentConditions::LESS_THAN_INCLUSIVE, '1.0.1', '1.0.0:semver', false],
        ];
    }

    /**
     * @dataProvider conditionParametersTraitVersionValues
     */
    public function testSegmentConditionMatchesTraitValueForSemver($operator, $traitValue, $conditionValue, $expectedResult)
    {
        $segmentCondition = (new SegmentConditionModel())
            ->withOperator($operator)
            ->withProperty('version')
            ->withValue($conditionValue);

        $this->assertEquals(
            $segmentCondition->matchesTraitValue($traitValue),
            $expectedResult
        );
    }

    public function ruleSegmentData()
    {
        return [
            [[], true],
            [[false], true],
            [[false, false], true],
            [[false, true], false],
            [[true, true], false],
        ];
    }

    /**
     * @dataProvider ruleSegmentData
     */
    public function testSegmentRuleNone($options, $result)
    {
        $this->assertEquals(
            SegmentEvaluator::none($options),
            $result
        );
    }
}
