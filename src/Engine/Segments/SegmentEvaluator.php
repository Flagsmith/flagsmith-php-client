<?php

namespace Flagsmith\Engine\Segments;

use Flagsmith\Engine\Environments\EnvironmentModel;
use Flagsmith\Engine\Identities\IdentityModel;
use Flagsmith\Engine\Identities\Traits\TraitModel;
use Flagsmith\Engine\Utils\HashingTrait;

class SegmentEvaluator
{
    use HashingTrait;
    /**
     * Get the identity segments.
     * @param EnvironmentModel $environment
     * @param IdentityModel $identity
     * @param array|null $overrideTraits
     * @return array
     */
    public static function getIdentitySegments(
        EnvironmentModel $environment,
        IdentityModel $identity,
        array $overrideTraits = null
    ): array {
        return array_filter(
            $environment->getProject()->getSegments()->getArrayCopy(),
            fn (SegmentModel $segment) => self::evaluateIdentityInSegment(
                $identity,
                $segment,
                $overrideTraits
            )
        );
    }

    /**
     * evaluate identity in segment.
     * @param IdentityModel $identity
     * @param SegmentModel $segment
     * @param array|null $overrideTraits
     * @return bool
     */
    public static function evaluateIdentityInSegment(
        IdentityModel $identity,
        SegmentModel $segment,
        array $overrideTraits = null
    ): bool {
        $rulesCount = count($segment->getRules());

        if (empty($rulesCount)) {
            return false;
        }

        $traitSegmentRules = [];
        foreach ($segment->getRules() as $rule) {
            $segmentRule = self::traitsMatchSegmentRule(
                $overrideTraits ?? $identity->getIdentityTraits()->getArrayCopy(),
                $rule,
                $segment->getId(),
                $identity->compositeKey()
            );

            $traitSegmentRules[] = $segmentRule;

            if (!$segmentRule) {
                break;
            }
        }

        return self::all($traitSegmentRules);
    }

    /**
     * traits match segment rule.
     * @param array $identityTraits
     * @param SegmentRuleModel $rule
     * @param mixed $segmentId
     * @param mixed $identityId
     * @return bool
     */
    private static function traitsMatchSegmentRule(
        array $identityTraits,
        SegmentRuleModel $rule,
        $segmentId,
        $identityId
    ): bool {
        $traitConditions = [true];
        $conditions = $rule->getConditions()->getArrayCopy();

        if (count($conditions) > 0) {
            $traitConditions = array_map(fn ($condition) => self::traitsMatchSegmentCondition(
                $identityTraits,
                $condition,
                $segmentId,
                $identityId
            ), $conditions);
        }

        $matchesCondition = $rule->matchingFunction()($traitConditions);

        $segmentRules = array_map(
            fn ($ruleNew) => self::traitsMatchSegmentRule($identityTraits, $ruleNew, $segmentId, $identityId),
            $rule->getRules()->getArrayCopy()
        );

        return $matchesCondition && (self::all($segmentRules));
    }

    /**
     * traits that match the segment condition.
     * @param array $identityTraits
     * @param SegmentConditionModel $condition
     * @param mixed $segmentId
     * @param mixed $identityId
     * @return bool
     */
    private static function traitsMatchSegmentCondition(
        array $identityTraits,
        SegmentConditionModel $condition,
        $segmentId,
        $identityId
    ): bool {
        if ($condition->getOperator() === SegmentConditions::PERCENTAGE_SPLIT) {
            $value = floatval($condition->getValue());
            return (self::getHashObject()->getHashedPercentageForObjectIds(
                [$segmentId, $identityId]
            ) <= $value);
        }

        $evaluations = array_filter(
            $identityTraits,
            fn (TraitModel $it) =>
            $it->getTraitKey() === $condition->getProperty()
        );

        $trait = count($evaluations) > 0 ? array_shift($evaluations) : false;

        return $trait ? $condition->matchesTraitValue($trait->getTraitValue()) : false;
    }

    /**
     * Check if all ocnditions are true.
     * @param array $list
     * @return bool
     */
    public static function all(array $list): bool
    {
        $evaluation = true;
        foreach ($list as $value) {
            $evaluation = $evaluation && $value;

            if (!$evaluation) {
                break;
            }
        }

        return $evaluation;
    }

    /**
     * Check if any ocnditions are true.
     * @param array $list
     * @return bool
     */
    public static function any(array $list): bool
    {
        $evaluation = false;
        foreach ($list as $value) {
            $evaluation = $evaluation || $value;

            if ($evaluation) {
                break;
            }
        }

        return $evaluation;
    }

    /**
     * Check if any ocnditions are true.
     * @param array $list
     * @return bool
     */
    public static function none(array $list): bool
    {
        return !self::any($list);
    }
}
