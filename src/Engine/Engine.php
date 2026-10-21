<?php

namespace Flagsmith\Engine;

use Flagsmith\Engine\Utils\Hashing;
use Flagsmith\Engine\Utils\LazyFlags;
use Flagsmith\Engine\Utils\Semver;
use Flagsmith\Engine\Utils\Types\Context\EvaluationContext;
use Flagsmith\Engine\Utils\Types\Context\FeatureContext;
use Flagsmith\Engine\Utils\Types\Context\SegmentRuleType;
use Flagsmith\Engine\Utils\Types\Context\SegmentCondition;
use Flagsmith\Engine\Utils\Types\Context\SegmentConditionOperator;
use Flagsmith\Engine\Utils\Types\Context\SegmentContext;
use Flagsmith\Engine\Utils\Types\Context\SegmentRule;
use Flagsmith\Engine\Utils\Types\Result\EvaluationResult;
use Flagsmith\Engine\Utils\Types\Result\FlagResult;
use Flagsmith\Engine\Utils\Types\Result\SegmentResult;
use Flow\JSONPath\JSONPath;
use Flow\JSONPath\JSONPathException;

class Engine
{
    public const STRONGEST_PRIORITY = -INF;
    public const WEAKEST_PRIORITY = +INF;

    private const VALID_CONTEXT_VALUE_TYPES = ['string', 'integer', 'boolean', 'double'];

    /**
     * Get the evaluation result for a given context.
     *
     * @param EvaluationContext $context The evaluation context.
     * @return EvaluationResult EvaluationResult containing the evaluated flags and matched segments.
     */
    public static function getEvaluationResult($context): EvaluationResult
    {
        /** @var array<string, SegmentResult> */
        $evaluatedSegments = [];

        /** @var array<string, FeatureContext> */
        $evaluatedFeatures = [];

        /** @var array<string, SegmentContext> */
        $matchedSegmentsByFeatureName = [];

        /** @var array<string, FlagResult> */
        $evaluatedFlags = [];

        $context = self::getEnrichedContext($context);

        // Discard any caller-supplied `$.flags`: they are resolved lazily as
        // segment conditions read them. Without segments, nothing reads them.
        $resolvedFlags = null;
        if (!empty($context->segments)) {
            $context = clone $context;
            $resolvedFlags = new LazyFlags($context);
            $context->flags = $resolvedFlags;
        }

        $cycleHits = 0;

        foreach ($context->segments as $segment) {
            $matches = self::isContextInSegment($context, $segment);

            if ($resolvedFlags->getCycleHits() !== $cycleHits) {
                // Evaluating this segment cut a cycle, so it matched, or failed to,
                // on a flag that could not be resolved. Neither verdict is sound.
                $cycleHits = $resolvedFlags->getCycleHits();
                continue;
            }

            if (!$matches) {
                continue;
            }

            $segmentResult = new SegmentResult();
            $segmentResult->name = $segment->name;
            $segmentResult->metadata = $segment->metadata;
            $evaluatedSegments[] = $segmentResult;

            foreach ($segment->overrides as $overrideFeature) {
                $featureName = $overrideFeature->name;
                if (!self::overrideWins($overrideFeature, $evaluatedFeatures[$featureName] ?? null)) {
                    continue;
                }

                $evaluatedFeatures[$featureName] = $overrideFeature;
                $matchedSegmentsByFeatureName[$featureName] = $segment;
            }
        }

        foreach ($context->features as $feature) {
            $featureName = $feature->name;
            $evaluatedFeature = $evaluatedFeatures[$featureName] ?? null;
            $reason = 'DEFAULT';
            if ($evaluatedFeature) {
                $feature = $evaluatedFeature;
                $reason = "TARGETING_MATCH; segment={$matchedSegmentsByFeatureName[$featureName]->name}";
            }

            $evaluatedFlags[$featureName] = self::getFlagResultFromFeatureContext(
                $feature,
                $context->identity?->key,
                $reason,
            );
        }

        if ($resolvedFlags !== null) {
            $context->flags = null; // Frees both by reference counting, not by the cycle collector
        }

        if ($resolvedFlags?->isUsed()) {
            // Only reached when a segment condition read a flag. Those results take
            // precedence: they were resolved with the cycle guard, unlike the
            // single-pass recomputation above.
            foreach ($resolvedFlags->getResolved() as $featureName => $flag) {
                $evaluatedFlags[$featureName] = $flag;
            }
            foreach ($resolvedFlags->getCyclic() as $featureName) {
                if (isset($evaluatedFlags[$featureName])) {
                    $evaluatedFlags[$featureName]->reason = LazyFlags::CIRCULAR_DEPENDENCY_REASON;
                }
            }
        }

        $result = new EvaluationResult();
        $result->flags = $evaluatedFlags;
        $result->segments = $evaluatedSegments;
        return $result;
    }

    /**
     * Get an enriched evaluation context with derived values:
     * - `$.identity.key` if missing
     * Returns a cloned context if any enrichment is applied.
     * Returns the original context if no enrichment is needed.
     * @param EvaluationContext $context
     * @return EvaluationContext
     */
    private static function getEnrichedContext(EvaluationContext $context): EvaluationContext
    {
        if ($context->identity !== null && $context->identity->key === null) {
            $context = clone $context;
            $context->identity = clone $context->identity;
            $context->identity->key = "{$context->environment->key}_{$context->identity->identifier}";
        }
        return $context;
    }

    /**
     * Whether a segment override takes precedence over the one held so far.
     *
     * Lower priority wins, and the first seen wins a tie, so that precedence
     * follows context order rather than the order segments happen to be
     * evaluated in.
     * @internal
     * @param FeatureContext $candidate
     * @param ?FeatureContext $incumbent
     * @return bool
     */
    public static function overrideWins($candidate, $incumbent): bool
    {
        return $incumbent === null
            || ($candidate->priority ?? self::WEAKEST_PRIORITY) < ($incumbent->priority ?? self::WEAKEST_PRIORITY);
    }

    /**
     * @internal
     * @param EvaluationContext $context
     * @param SegmentContext $segment
     * @return bool
     */
    public static function isContextInSegment($context, $segment): bool
    {
        if (empty($segment->rules)) {
            return false;
        }

        foreach ($segment->rules as $rule) {
            if (!self::_contextMatchesRule($context, $rule, $segment->key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @internal
     * @param FeatureContext $feature
     * @param ?string $splitKey
     * @param string $reason
     * @return FlagResult
     */
    public static function getFlagResultFromFeatureContext($feature, $splitKey, $reason = 'DEFAULT')
    {
        $variantKey = null;

        if ($splitKey !== null && !empty($feature->variants)) {
            // Default to the control bucket; a matched named variant overrides this.
            $variantKey = 'control';

            $hashing = new Hashing();
            $percentageValue = $hashing->getHashedPercentageForObjectIds([
                $feature->key,
                $splitKey,
            ]);

            // Ensure variants are selected consistently
            $variants = $feature->variants;
            usort($variants, fn ($a, $b) => $a->priority <=> $b->priority);

            $startPercentage = 0.0;
            foreach ($variants as $variant) {
                $limit = $variant->weight + $startPercentage;
                if (
                    $startPercentage <= $percentageValue &&
                    $percentageValue < $limit
                ) {
                    $flag = new FlagResult();
                    $flag->name = $feature->name;
                    $flag->enabled = $feature->enabled;
                    $flag->value = $variant->value;
                    $flag->variant = $variant->key;
                    $flag->reason = "SPLIT; weight={$variant->weight}";
                    $flag->metadata = $feature->metadata;
                    return $flag;
                }
                $startPercentage = $limit;
            }
        }

        $flag = new FlagResult();
        $flag->name = $feature->name;
        $flag->enabled = $feature->enabled;
        $flag->value = $feature->value;
        $flag->variant = $variantKey;
        $flag->reason = $reason;
        $flag->metadata = $feature->metadata;
        return $flag;
    }

    /**
     * @param EvaluationContext $context
     * @param SegmentRule $rule
     * @param string $segmentKey
     * @return bool
     */
    private static function _contextMatchesRule(
        $context,
        $rule,
        $segmentKey,
    ): bool {
        $any = false;
        foreach ($rule->conditions as $condition) {
            $conditionMatches = self::_contextMatchesCondition(
                $context,
                $condition,
                $segmentKey,
            );

            switch ($rule->type) {
                case SegmentRuleType::ALL:
                    if (!$conditionMatches) {
                        return false;
                    }
                    break;
                case SegmentRuleType::NONE:
                    if ($conditionMatches) {
                        return false;
                    }
                    break;
                case SegmentRuleType::ANY:
                    if ($conditionMatches) {
                        $any = true;
                        break 2;
                    }
                    break;
            }
        }

        if ($rule->type === SegmentRuleType::ANY && !$any && !empty($rule->conditions)) {
            return false;
        }

        $anySubRule = false;
        foreach ($rule->rules as $subRule) {
            $subRuleMatches = self::_contextMatchesRule(
                $context,
                $subRule,
                $segmentKey,
            );

            switch ($rule->type) {
                case SegmentRuleType::ALL:
                    if (!$subRuleMatches) {
                        return false;
                    }
                    break;
                case SegmentRuleType::NONE:
                    if ($subRuleMatches) {
                        return false;
                    }
                    break;
                case SegmentRuleType::ANY:
                    if ($subRuleMatches) {
                        $anySubRule = true;
                        break 2;
                    }
                    break;
            }
        }

        if ($rule->type === SegmentRuleType::ANY && !$anySubRule && !empty($rule->rules)) {
            return false;
        }

        return true;
    }

    /**
     * @param EvaluationContext $context
     * @param SegmentCondition $condition
     * @param string $segmentKey
     * @return bool
     */
    private static function _contextMatchesCondition(
        $context,
        $condition,
        $segmentKey,
    ): bool {
        // NOTE: Currently, the only supported condition with a blank property is percentage split. In this
        // case, we use the identity key as context value. This is mainly to support legacy segments created
        // before we introduced JSONPath support.
        if ($condition->operator === SegmentConditionOperator::PERCENTAGE_SPLIT && empty($condition->property)) {
            $contextValue = $context->identity?->key;
        } else {
            $contextValue = self::_getContextValue($context, $condition->property);
        }

        $cast = self::_getCaster($contextValue);

        switch ($condition->operator) {
            case SegmentConditionOperator::IN:
                if ($contextValue === null || gettype($contextValue) === 'boolean') {
                    return false;
                }
                if (is_array($condition->value)) {
                    $inValues = $condition->value;
                } else {
                    try {
                        $inValues = json_decode(
                            $condition->value,
                            associative: false,  // Possibly catch objects
                            flags: \JSON_THROW_ON_ERROR,
                        );
                        if (!is_array($inValues)) {
                            throw new \ValueError('Invalid JSON array');
                        }
                    } catch (\JsonException | \ValueError) {
                        $inValues = explode(',', $condition->value);
                    }
                }
                $contextValue = strval($contextValue);
                $inValues = array_map('strval', $inValues);
                return in_array($contextValue, $inValues, strict: true);

            case SegmentConditionOperator::PERCENTAGE_SPLIT:
                if ($contextValue === null) {
                    return false;
                }
                if (!is_numeric($condition->value)) {
                    return false;
                }

                $hashing = new Hashing();
                $threshold = $hashing->getHashedPercentageForObjectIds(
                    [$segmentKey, $contextValue],
                );
                return $threshold <= ((float) $condition->value);

            case SegmentConditionOperator::MODULO:
                if (!is_numeric($contextValue)) {
                    return false;
                }

                $parts = explode('|', (string) $condition->value);
                if (count($parts) !== 2) {
                    return false;
                }

                [$divisor, $remainder] = $parts;
                if (!is_numeric($divisor) || !is_numeric($remainder)) {
                    return false;
                }

                return fmod($contextValue, $divisor) === ((float) $remainder);

            case SegmentConditionOperator::IS_NOT_SET:
                return $contextValue === null;

            case SegmentConditionOperator::IS_SET:
                return $contextValue !== null;

            case SegmentConditionOperator::CONTAINS:
                return is_string($contextValue) && is_string($condition->value)
                    && str_contains($contextValue, $condition->value);

            case SegmentConditionOperator::NOT_CONTAINS:
                return is_string($contextValue) && is_string($condition->value)
                    && !str_contains($contextValue, $condition->value);

            case SegmentConditionOperator::REGEX:
                return (bool) preg_match("/{$condition->value}/", (string) $contextValue);
        }

        if ($contextValue === null) {
            return false;
        }

        $operator = match ($condition->operator) {
            SegmentConditionOperator::EQUAL => '==',
            SegmentConditionOperator::NOT_EQUAL => '!=',
            SegmentConditionOperator::GREATER_THAN => '>',
            SegmentConditionOperator::GREATER_THAN_INCLUSIVE => '>=',
            SegmentConditionOperator::LESS_THAN => '<',
            SegmentConditionOperator::LESS_THAN_INCLUSIVE => '<=',
            default => null,
        };

        if ($operator === null) {
            return false;
        }

        if (Semver::isSemver($condition->value) && is_string($contextValue)) {
            $actualVersion = Semver::removeSemverSuffix($condition->value);
            return version_compare($contextValue, $actualVersion, $operator);
        }

        return match ($operator) {
            '==' => $contextValue === $cast($condition->value),
            '!=' => $contextValue !== $cast($condition->value),
            '>' => $contextValue > $cast($condition->value),
            '>=' => $contextValue >= $cast($condition->value),
            '<' => $contextValue < $cast($condition->value),
            '<=' => $contextValue <= $cast($condition->value),
        };
    }

    /**
     * Return a trait value by name, or a context value by JSONPath, or null
     * @param EvaluationContext $context
     * @param string $property
     * @return ?mixed
     */
    private static function _getContextValue($context, $property)
    {
        if ($context->identity !== null) {
            $hasTrait = array_key_exists($property, $context->identity->traits);
            if ($hasTrait) {
                return $context->identity->traits[$property];
            }
        }

        if (str_starts_with($property, '$.')) {
            try {
                $jsonpath = new JSONPath($context);
                $results = $jsonpath->find($property)->getData();
            } catch (JSONPathException) {
                return null;
            }

            if (empty($results)) {
                return null;
            }

            if (in_array(gettype($results[0]), self::VALID_CONTEXT_VALUE_TYPES)) {
                return $results[0];
            };
        }

        return null;
    }

    /**
     * Get a condition value type caster according to a context value
     * @param mixed $contextValue
     * @return ?callable
     */
    private static function _getCaster($contextValue): ?callable
    {
        if (!in_array(gettype($contextValue), self::VALID_CONTEXT_VALUE_TYPES)) {
            return null;
        }

        return match (gettype($contextValue)) {
            'boolean' => fn ($v) => !in_array($v, ['False', 'false']),
            'string' => 'strval',
            'integer' => fn ($v) => is_numeric($v) ? (int) $v : $v,
            'double' => fn ($v) => is_numeric($v) ? (float) $v : $v,
        };
    }
}
