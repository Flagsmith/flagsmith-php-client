<?php

namespace Flagsmith\Utils;

use Flagsmith\Engine\Engine;
use Flagsmith\Engine\Utils\Types\Context\EnvironmentContext;
use Flagsmith\Engine\Utils\Types\Context\EvaluationContext;
use Flagsmith\Engine\Utils\Types\Context\FeatureContext;
use Flagsmith\Engine\Utils\Types\Context\FeatureValue;
use Flagsmith\Engine\Utils\Types\Context\IdentityContext;
use Flagsmith\Engine\Utils\Types\Context\SegmentCondition;
use Flagsmith\Engine\Utils\Types\Context\SegmentConditionOperator;
use Flagsmith\Engine\Utils\Types\Context\SegmentContext;
use Flagsmith\Engine\Utils\Types\Context\SegmentRule;
use Flagsmith\Engine\Utils\Types\Context\SegmentRuleType;

class Mappers
{
    /**
     * Parse the environment document into an EvaluationContext object
     * @param object $environmentDocument
     * @return EvaluationContext
     */
    public static function mapEnvironmentDocumentToContext($environmentDocument): EvaluationContext
    {
        $context = new EvaluationContext();

        $context->environment = new EnvironmentContext();
        $context->environment->key = $environmentDocument->api_key;
        $context->environment->name = $environmentDocument->name;

        $context->segments = [];
        foreach ($environmentDocument->project->segments as $srcSegment) {
            $segment = new SegmentContext();
            $segment->key = (string) $srcSegment->id;
            $segment->name = $srcSegment->name;
            $segment->rules = self::_mapEnvironmentDocumentRulesToContextRules($srcSegment->rules ?? []);
            $segment->metadata = [
                'source' => 'api',
                'flagsmith_id' => $srcSegment->id,
            ];
            $context->segments[$segment->key] = $segment;

            $overrides = self::_mapEnvironmentDocumentFeatureStatesToFeatureContexts($srcSegment->feature_states ?? []);
            $segment->overrides = array_values($overrides);
        }

        $identityOverrides = self::_mapIdentityOverridesToSegments($environmentDocument->identity_overrides ?? []);
        $context->segments = array_merge($context->segments, $identityOverrides);

        $context->features = self::_mapEnvironmentDocumentFeatureStatesToFeatureContexts($environmentDocument->feature_states ?? []);

        return $context;
    }

    /**
     * Attaches identity context into a new evaluation context based on $context
     * @param EvaluationContext $context
     * @param string $identifier
     * @param object $traits
     * @return EvaluationContext
     */
    public static function mapContextAndIdentityToContext($context, $identifier, $traits): EvaluationContext
    {
        $identity = new IdentityContext();
        $identity->identifier = $identifier;
        $identity->traits = (array) $traits;

        $context = $context->deepClone();
        $context->identity = $identity;
        return $context;
    }

    /**
     * @param array<object> $srcRules
     * @return array<SegmentRule>
     */
    private static function _mapEnvironmentDocumentRulesToContextRules($srcRules)
    {
        $rules = [];
        foreach ($srcRules as $srcRule) {
            $rule = new SegmentRule();
            $rule->type = SegmentRuleType::from($srcRule->type);

            $rule->conditions = [];
            foreach ($srcRule->conditions ?? [] as $jsonCondition) {
                $condition = new SegmentCondition();
                $condition->property = $jsonCondition->property_;
                $condition->operator = SegmentConditionOperator::from($jsonCondition->operator);
                $condition->value = $jsonCondition->value;
                $rule->conditions[] = $condition;
            }

            $rule->rules = $srcRule->rules
                ? self::_mapEnvironmentDocumentRulesToContextRules($srcRule->rules)
                : [];

            $rules[] = $rule;
        }

        return $rules;
    }

    /**
     * @param array<object> $featureStates
     * @return array<string, FeatureContext>
     */
    private static function _mapEnvironmentDocumentFeatureStatesToFeatureContexts($featureStates)
    {
        $featureContexts = [];
        foreach ($featureStates as $featureState) {
            $feature = new FeatureContext();
            $feature->key = (string) ($featureState->django_id ?? $featureState->featurestate_uuid);
            $feature->name = $featureState->feature->name;
            $feature->enabled = $featureState->enabled;
            $feature->value = $featureState->feature_state_value;
            $feature->priority = $featureState->feature_segment?->priority ?? null;
            $feature->metadata = ['flagsmith_id' => $featureState->feature->id];
            $feature->variants = [];
            $multivariateFeatureStateValues = ((array) $featureState->multivariate_feature_state_values) ?? [];
            $multivariateFeatureStateValueUUIDs = array_column($multivariateFeatureStateValues, 'mv_fs_value_uuid');
            sort($multivariateFeatureStateValueUUIDs);  // For historical consistency
            foreach ($multivariateFeatureStateValues as $multivariateFeatureStateValue) {
                $variant = new FeatureValue();
                $variant->value = $multivariateFeatureStateValue->multivariate_feature_option->value;
                $variant->weight = $multivariateFeatureStateValue->percentage_allocation;
                $variant->priority = $multivariateFeatureStateValue->id
                    ?? array_search($multivariateFeatureStateValue->mv_fs_value_uuid, $multivariateFeatureStateValueUUIDs);
                $feature->variants[] = $variant;
            }

            $featureContexts[$feature->name] = $feature;
        }

        return $featureContexts;
    }

    /**
     * @param array<object> $identityOverrides
     * @return array<string, SegmentContext>
     */
    private static function _mapIdentityOverridesToSegments($identityOverrides)
    {
        /** @var array<string, array<string>> */
        $featuresToIdentifiers = [];
        foreach ($identityOverrides as $identityOverride) {
            $identityFeatures = ((array) $identityOverride->identity_features) ?? [];
            // Sort by feature name to ensure consistent serialization order for $overridesKey
            uksort($identityFeatures, fn ($a, $b) => strcasecmp($a->feature->name, $b->feature->name));
            if (empty($identityFeatures)) {
                continue;
            }

            /** @var array<array<mixed>> */
            $overridesKey = [];
            foreach ($identityFeatures as $featureState) {
                $part = [
                    $featureState->feature->id,
                    $featureState->feature->name,
                    $featureState->enabled,
                    $featureState->feature_state_value,
                ];
                $overridesKey[] = $part;
            }
            $featuresToIdentifiers[serialize($overridesKey)][] = $identityOverride->identifier;
        }

        /** @var array<string, SegmentContext> */
        $segments = [];
        foreach ($featuresToIdentifiers as $serializedOverridesKey => $identifiers) {
            $segment = new SegmentContext();
            $segment->key = '';  // Not used in identity overrides
            $segment->name = 'identity_overrides';
            $segment->metadata = ['source' => 'identity_override'];

            $identifiersCondition = new SegmentCondition();
            $identifiersCondition->property = '$.identity.identifier';
            $identifiersCondition->operator = SegmentConditionOperator::IN;
            $identifiersCondition->value = $identifiers;

            $identifiersRule = new SegmentRule();
            $identifiersRule->type = SegmentRuleType::ALL;
            $identifiersRule->conditions = [$identifiersCondition];
            $segment->rules = [$identifiersRule];

            $segment->overrides = [];
            foreach (unserialize($serializedOverridesKey) as $overrideKey) {
                [$featureId, $featureName, $enabled, $value] = $overrideKey;
                $feature = new FeatureContext();
                $feature->key = '';  // Not used in identity overrides
                $feature->name = $featureName;
                $feature->enabled = $enabled;
                $feature->value = $value;
                $feature->priority = Engine::STRONGEST_PRIORITY;
                $feature->metadata = ['flagsmith_id' => $featureId];
                $segment->overrides[] = $feature;
            }

            $segmentKey = hash('sha256', $serializedOverridesKey);
            $segments[$segmentKey] = $segment;
        }

        return $segments;
    }
}
