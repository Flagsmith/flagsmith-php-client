<?php

namespace Flagsmith\Engine\Utils\Types\Context;

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
        $context = new EvaluationContext();

        $context->environment = new EnvironmentContext();
        $context->environment->key = $jsonContext->environment->key;
        $context->environment->name = $jsonContext->environment->name;

        if (!empty($jsonContext->identity)) {
            $context->identity = new IdentityContext();
            $context->identity->key = $jsonContext->identity->key;
            $context->identity->identifier = $jsonContext->identity->identifier;
            $context->identity->traits = (array) ($jsonContext->identity->traits ?? []);
        }

        $context->segments = [];
        foreach (($jsonContext->segments ?? []) as $jsonSegment) {
            $segment = new SegmentContext();
            $segment->key = $jsonSegment->key;
            $segment->name = $jsonSegment->name;
            $segment->rules = self::_convertRules($jsonSegment->rules ?? []);
            $segment->overrides = array_values(self::_convertFeatures($jsonSegment->overrides ?? []));
            $segment->metadata = (array) ($jsonSegment->metadata ?? []);
            $context->segments[$segment->key] = $segment;
        }

        $context->features = self::_convertFeatures($jsonContext->features ?? []);

        return $context;
    }

    /**
     * Deep clone the EvaluationContext
     * @return EvaluationContext
     */
    public function deepClone(): self
    {
        return EvaluationContext::fromJsonObject($this);
    }

    /**
     * @param array<object> $jsonRules
     * @return array<SegmentRule>
     */
    private static function _convertRules($jsonRules)
    {
        $rules = [];
        foreach ($jsonRules as $jsonRule) {
            $rule = new SegmentRule();
            $rule->type = $jsonRule->type instanceof SegmentRuleType
                ? $jsonRule->type
                : SegmentRuleType::from($jsonRule->type);

            $rule->conditions = [];
            foreach ($jsonRule->conditions ?? [] as $jsonCondition) {
                $condition = new SegmentCondition();
                $condition->property = $jsonCondition->property;
                $condition->operator = $jsonCondition->operator instanceof SegmentConditionOperator
                    ? $jsonCondition->operator
                    : SegmentConditionOperator::from($jsonCondition->operator);
                $condition->value = $jsonCondition->value;
                $rule->conditions[] = $condition;
            }

            $rule->rules = empty($jsonRule->rules)
                ? []
                : self::_convertRules($jsonRule->rules);

            $rules[] = $rule;
        }

        return $rules;
    }

    /**
     * @param array<object> $jsonFeatures
     * @return array<string, FeatureContext>
     */
    private static function _convertFeatures($jsonFeatures): array
    {
        $features = [];
        foreach ($jsonFeatures as $jsonFeature) {
            $feature = new FeatureContext();
            $feature->key = $jsonFeature->key;
            $feature->feature_key = (string) $jsonFeature->feature_key;
            $feature->name = $jsonFeature->name;
            $feature->enabled = $jsonFeature->enabled;
            $feature->value = $jsonFeature->value;
            $feature->priority = $jsonFeature->priority ?? null;
            $feature->variants = [];
            foreach ($jsonFeature->variants ?? [] as $jsonVariant) {
                $variant = new FeatureValue();
                $variant->value = $jsonVariant->value;
                $variant->weight = $jsonVariant->weight;
                $variant->priority = $jsonVariant->priority;
                $feature->variants[] = $variant;
            }

            $features[$jsonFeature->name] = $feature;
        }

        return $features;
    }
}
