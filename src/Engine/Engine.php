<?php

namespace Flagsmith\Engine;

use Flagsmith\Engine\Environments\EnvironmentModel;
use Flagsmith\Engine\Features\FeatureStateModel;
use Flagsmith\Engine\Identities\IdentityModel;
use Flagsmith\Engine\Segments\SegmentEvaluator;
use Flagsmith\Engine\Utils\Exceptions\FeatureStateNotFound;
use Flagsmith\Engine\Utils\Hashing;
use Flagsmith\Engine\Utils\Semver;
use Flagsmith\Engine\Utils\StringValue;
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

    /**
     * Get the evaluation result for a given context.
     *
     * @param EvaluationContext $context The evaluation context.
     * @return EvaluationResult EvaluationResult containing the context, flags, and segments
     */
    public static function getEvaluationResult($context): EvaluationResult
    {
        /** @var array<string, SegmentResult> */
        $evaluatedSegments = [];

        /** @var array<string, FeatureContext> */
        $evaluatedFeatures = [];

        /** @var array<string, SegmentContext> */
        $matchedSegmentsByFeatureKey = [];

        /** @var array<string, FlagResult> */
        $evaluatedFlags = [];

        foreach ($context->segments as $segment) {
            if (!self::isContextInSegment($context, $segment)) {
                continue;
            }

            $segmentResult = new SegmentResult();
            $segmentResult->key = $segment->key;
            $segmentResult->name = $segment->name;
            $evaluatedSegments[] = $segmentResult;

            if (empty($segment->overrides)) {
                continue;
            }

            foreach ($segment->overrides as $overrideFeature) {
                $featureKey = $overrideFeature->feature_key;
                $evaluatedFeature = $evaluatedFeatures[$featureKey] ?? null;
                if ($evaluatedFeature) {
                    $overrideWinsPriority =
                        ($overrideFeature->priority ?? self::WEAKEST_PRIORITY) <
                        ($evaluatedFeature->priority ?? self::WEAKEST_PRIORITY);
                    if (!$overrideWinsPriority) {
                        continue;
                    }
                }

                $evaluatedFeatures[$featureKey] = $overrideFeature;
                $matchedSegmentsByFeatureKey[$featureKey] = $segment;
            }
        }

        foreach ($context->features as $feature) {
            $featureKey = $feature->feature_key;
            $featureName = $feature->name;
            $evaluatedFeature = $evaluatedFeatures[$featureKey] ?? null;
            if ($evaluatedFeature) {
                $evaluatedFlags[$featureName] = self::getFlagResultFromSegmentContext(
                    $evaluatedFeature,
                    $matchedSegmentsByFeatureKey[$featureKey],
                );
                continue;
            }

            $evaluatedFlags[$featureName] = self::getFlagResultFromFeatureContext(
                $feature,
                $context->identity?->key,
            );
        }

        $result = new EvaluationResult();
        $result->flags = $evaluatedFlags;
        $result->segments = $evaluatedSegments;
        return $result;
    }

    /**
     * @param EvaluationContext $context
     * @param SegmentContext $segment
     * @return bool
     */
    private static function isContextInSegment($context, $segment): bool
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
     * @param FeatureContext $feature
     * @param ?string $splitKey
     * @return FlagResult
     */
    private static function getFlagResultFromFeatureContext($feature, $splitKey)
    {
        if ($splitKey !== null && !empty($feature->variants)) {
            $hashing = new Hashing();
            $percentageValue = $hashing->getHashedPercentageForObjectIds([
                $feature->key,
                $splitKey,
            ]);

            $startPercentage = 0.0;
            foreach ($feature->variants as $variant) {
                $limit = $variant->weight + $startPercentage;
                if (
                    $startPercentage <= $percentageValue &&
                    $percentageValue < $limit
                ) {
                    $flag = new FlagResult();
                    $flag->feature_key = $feature->feature_key;
                    $flag->name = $feature->name;
                    $flag->enabled = $feature->enabled;
                    $flag->value = $variant->value;
                    $flag->reason = "SPLIT; weight={$variant->weight}";
                    return $flag;
                }
                $startPercentage = $limit;
            }
        }

        $flag = new FlagResult();
        $flag->feature_key = $feature->feature_key;
        $flag->name = $feature->name;
        $flag->enabled = $feature->enabled;
        $flag->value = $feature->value;
        $flag->reason = 'DEFAULT';
        return $flag;
    }

    /**
     * @param FeatureContext $feature
     * @param SegmentContext $segment
     * @return FlagResult
     */
    private static function getFlagResultFromSegmentContext($feature, $segment)
    {
        $flag = new FlagResult();
        $flag->feature_key = $feature->feature_key;
        $flag->name = $feature->name;
        $flag->enabled = $feature->enabled;
        $flag->value = $feature->value;
        $flag->reason = "TARGETING_MATCH; segment={$segment->name}";
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

        if ($rule->type === SegmentRuleType::ANY && !$any) {
            return false;
        }

        foreach ($rule->rules as $subRule) {
            $ruleMatches = self::_contextMatchesRule(
                $context,
                $subRule,
                $segmentKey,
            );
            if (!$ruleMatches) {
                return false;
            }
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
        $contextValue = self::_getContextValue($context, $condition->property);

        switch ($condition->operator) {
            case SegmentConditionOperator::IN:
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
                $inValues = array_map(fn ($value) => StringValue::from($value), $inValues);
                $contextValue = StringValue::from($contextValue);
                return in_array($contextValue, $inValues, strict: true);

            case SegmentConditionOperator::PERCENTAGE_SPLIT:
                if (!is_numeric($condition->value)) {
                    return false;
                }

                /** @var array<string> $objectIds */
                if ($contextValue !== null) {
                    $objectIds = [$segmentKey, $contextValue];
                } elseif ($context->identity !== null) {
                    $objectIds = [$segmentKey, $context->identity->key];
                } else {
                    return false;
                }

                $hashing = new Hashing();
                $threshold = $hashing->getHashedPercentageForObjectIds(
                    $objectIds,
                );
                return $threshold <= floatval($condition->value);

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

                return floatval($contextValue) % $divisor === $remainder;

            case SegmentConditionOperator::IS_NOT_SET:
                return $contextValue === null;

            case SegmentConditionOperator::IS_SET:
                return $contextValue !== null;

            case SegmentConditionOperator::CONTAINS:
                return str_contains($contextValue, $condition->value);

            case SegmentConditionOperator::NOT_CONTAINS:
                return !str_contains($contextValue, $condition->value);

            case SegmentConditionOperator::REGEX:
                return boolval(
                    preg_match("/{$condition->value}/", $contextValue),
                );
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

        if (is_string($contextValue) && Semver::isSemver($contextValue)) {
            $contextValue = Semver::removeSemverSuffix($contextValue);
            return $operator !== null &&
                version_compare($contextValue, $condition->value, $operator);
        }

        return match ($operator) {
            '==' => $contextValue == $condition->value,
            '!=' => $contextValue != $condition->value,
            '>' => $contextValue > $condition->value,
            '>=' => $contextValue >= $condition->value,
            '<' => $contextValue < $condition->value,
            '<=' => $contextValue <= $condition->value,
            default => false,
        };
    }

    /**
     * @param EvaluationContext $context
     * @param string $property
     * @return mixed|array<mixed>|null
     */
    private static function _getContextValue($context, $property)
    {
        if (str_starts_with($property, '$.')) {
            try {
                $json = new JSONPath($context);
                $results = $json->find($property)->getData();
            } catch (JSONPathException) {
                // The unlikely case when a trait starts with "$." but isn't JSONPath
                $escapedProperty = addslashes($property);
                $path = "$.identity.traits['{$escapedProperty}']";
                $json = new JSONPath($context);
                $results = $json->find($path)->getData();
            }

            return match (count($results)) {
                0 => null,
                1 => $results[0],
                default => $results,
            };
        }

        if ($context->identity !== null) {
            return $context->identity->traits[$property] ?? null;
        }

        return null;
    }

    /**
     * Get the environment feature states.
     * @param EnvironmentModel $environment
     * @return array
     */
    public static function getEnvironmentFeatureStates(EnvironmentModel $environment): array
    {
        if ($environment->getProject()->getHideDisabledFlags()) {
            return array_filter(
                $environment->getFeatureStates()->getArrayCopy(),
                fn (FeatureStateModel $fs) => $fs->getEnabled()
            );
        }

        return $environment->getFeatureStates()->getArrayCopy();
    }

    /**
     * get the environment feature state.
     * @param EnvironmentModel $environment
     * @param string $featureName
     * @return FeatureStateModel
     */
    public static function getEnvironmentFeatureState(
        EnvironmentModel $environment,
        string $featureName
    ): FeatureStateModel {
        $featureStates = $environment
            ->getFeatureStates()
            ->getArrayCopy();
        $filteredFeatureStates = array_filter(
            $featureStates,
            fn (FeatureStateModel $fs)
                => $fs->getFeature()->getName() === $featureName
        );

        if (count($filteredFeatureStates) === 0) {
            throw new FeatureStateNotFound();
        }

        return array_shift($filteredFeatureStates);
    }

    /**
     * get the identity feature states.
     * @param EnvironmentModel $environment
     * @param IdentityModel $identity
     * @param array|null $overrideTraits
     * @return array
     */
    public static function getIdentityFeatureStates(
        EnvironmentModel $environment,
        IdentityModel $identity,
        ?array $overrideTraits = null
    ): array {
        $featureStates = self::_getIdentityFeatureStatesDict(
            $environment,
            $identity,
            $overrideTraits
        );

        if ($environment->getProject()->getHideDisabledFlags()) {
            $filteredFeatureStates = [];
            foreach ($featureStates as $featureState) {
                if ($featureState->getEnabled()) {
                    $filteredFeatureStates[] = $featureState;
                }
            }
            return $filteredFeatureStates;
        }

        return array_values($featureStates);
    }

    /**
     * get the identity feature state.
     * @param EnvironmentModel $environment
     * @param IdentityModel $identity
     * @param string $featureName
     * @param array|null $overrideTraits
     * @return FeatureStateModel
     */
    public static function getIdentityFeatureState(
        EnvironmentModel $environment,
        IdentityModel $identity,
        string $featureName,
        ?array $overrideTraits = null
    ): FeatureStateModel {
        $featureStates = self::_getIdentityFeatureStatesDict(
            $environment,
            $identity,
            $overrideTraits
        );

        $matchingFeatureState = null;
        foreach ($featureStates as $name => $featureState) {
            if ($name === $featureName) {
                $matchingFeatureState = $featureState;
            }
        }

        if (empty($matchingFeatureState)) {
            throw new FeatureStateNotFound();
        }

        return $matchingFeatureState;
    }

    /**
     * get feature states list.
     * @param EnvironmentModel $environment
     * @param IdentityModel $identity
     * @param array|null $overrideTraits
     * @return array
     */
    private static function _getIdentityFeatureStatesDict(
        EnvironmentModel $environment,
        IdentityModel $identity,
        ?array $overrideTraits = null
    ): array {
        $featureStates = [];
        foreach ($environment->getFeatureStates() as $fs) {
            $featureStates[$fs->getFeature()->getName()] = $fs;
        }

        $identitySegments = SegmentEvaluator::getIdentitySegments($environment, $identity, $overrideTraits);

        foreach ($identitySegments as $is) {
            foreach ($is->getFeatureStates() as $fs) {
                $feature = $fs->getFeature();
                $existing = $featureStates[$feature->getName()];
                if ($existing != null && $existing->isHigherPriority($fs)) {
                    continue;
                }

                $featureStates[$fs->getFeature()->getName()] = $fs;
            }
        }

        foreach ($identity->getIdentityFeatures() as $if) {
            if (isset($featureStates[$if->getFeature()->getName()])) {
                $featureStates[$if->getFeature()->getName()] = $if;
            }
        }

        return $featureStates;
    }
}
