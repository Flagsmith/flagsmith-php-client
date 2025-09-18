<?php
namespace Flagsmith\Engine\Utils\Types\Context;

// TODO: Port this to https://wiki.php.net/rfc/dataclass
class EvaluationContext
{
    /** @var EnvironmentContext */
    public $environment;

    /** @var ?IdentityContext */
    public $identity;

    /** @var array<string, SegmentContext> */
    public $segments;

    /** @var array<string, FeatureContext> */
    public $features;

    /**
     * @param object $jsonContext
     * @return EvaluationContext
     */
    public static function fromJsonObject($jsonContext)
    {
        $context = new EvaluationContext;

        $context->environment = new EnvironmentContext;
        $context->environment->key = $jsonContext->environment->key;
        $context->environment->name = $jsonContext->environment->name;

        $context->identity = new IdentityContext;
        $context->identity->key = $jsonContext->identity->key;
        $context->identity->identifier = $jsonContext->identity->identifier;
        $context->identity->traits = $jsonContext->identity->traits;

        $context->segments = [];
        foreach ($jsonContext->segments as $jsonSegment) {
            $segment = new SegmentContext;
            $segment->key = $jsonSegment->key;
            $segment->name = $jsonSegment->name;
            $segment->rules = _convertRules($jsonSegment->rules ?? []);
            $segment->overrides = _convertFeatures($jsonSegment->overrides ?? []);
            $context->segments[$segment->key] = $segment;
        }

        $context->features = _convertFeatures($jsonContext->features ?? []);

        return $context;
    }
}

/**
 * @param array<object> $jsonRules
 * @return array<SegmentRule>
 */
function _convertRules($jsonRules)
{
    $rules = [];
    foreach ($jsonRules as $jsonRule) {
        $rule = new SegmentRule;
        $rule->type = RuleType::from($jsonRule->type);

        $rule->conditions = [];
        foreach (($jsonRule->conditions ?? []) as $jsonCondition) {
            $condition = new SegmentCondition;
            $condition->property = $jsonCondition->property;
            $condition->operator = SegmentConditionOperator::from($jsonCondition->operator);
            $condition->value = $jsonCondition->value;
            $rule->conditions[] = $condition;
        }

        $rule->rules = empty($jsonRule->rules) ? [] : _convertRules($jsonRule->rules);

        $rules[] = $rule;
    }

    return $rules;
}

/**
 * @param array<object> $jsonFeatures
 * @return array<FeatureContext>
 */
function _convertFeatures($jsonFeatures)
{
    $features = [];
    foreach ($jsonFeatures as $jsonFeature) {
        $feature = new FeatureContext;
        $feature->key = $jsonFeature->key;
        $feature->feature_key = $jsonFeature->feature_key;
        $feature->name = $jsonFeature->name;
        $feature->enabled = $jsonFeature->enabled;
        $feature->value = $jsonFeature->value;
        $feature->priority = $jsonFeature->priority;
        $feature->variants = [];
        foreach (($jsonFeature->variants ?? []) as $jsonVariant) {
            $variant = new FeatureValue;
            $variant->value = $jsonVariant->value;
            $variant->weight = $jsonVariant->weight;
            $feature->variants[] = $variant;
        }
        $features[] = $feature;
    }

    return $features;
}
