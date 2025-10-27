<?php

namespace FlagsmithTest\Utils;

use FlagsmithTest\ClientFixtures;
use Flagsmith\Engine\Utils\Types\Context\EnvironmentContext;
use Flagsmith\Engine\Utils\Types\Context\EvaluationContext;
use Flagsmith\Engine\Utils\Types\Context\SegmentRuleType;
use Flagsmith\Engine\Utils\Types\Context\SegmentConditionOperator;
use Flagsmith\Utils\Mappers;
use PHPUnit\Framework\TestCase;

class MappersTest extends TestCase
{
    public function testMapEnvironmentDocumentToContextProducesEvaluationContext(): void
    {
        // Given
        $environment = ClientFixtures::getEnvironmentModel();

        // When
        $context = Mappers::mapEnvironmentDocumentToContext($environment);

        // Then
        $this->assertInstanceOf(EvaluationContext::class, $context);
        $this->assertEquals('B62qaMZNwfiqT76p38ggrQ', $context->environment->key);
        $this->assertEquals('Test environment', $context->environment->name);
        $this->assertNull($context->identity);
        $this->assertCount(2, $context->segments);

        $this->assertArrayHasKey(0, $context->segments);
        $this->assertEquals(1, $context->segments[0]->key);
        $this->assertEquals('Test segment', $context->segments[0]->name);
        $this->assertCount(1, $context->segments[0]->rules);
        $this->assertEmpty($context->segments[0]->overrides);
        $this->assertEquals('api', $context->segments[0]->metadata['source']);
        $this->assertEquals('1', $context->segments[0]->metadata['flagsmith_id']);

        $this->assertEquals(SegmentRuleType::ALL, $context->segments[0]->rules[0]->type);
        $this->assertEmpty($context->segments[0]->rules[0]->conditions);
        $this->assertCount(1, $context->segments[0]->rules[0]->rules);

        $this->assertEquals(SegmentRuleType::ALL, $context->segments[0]->rules[0]->rules[0]->type);
        $this->assertCount(1, $context->segments[0]->rules[0]->rules[0]->conditions);
        $this->assertEmpty($context->segments[0]->rules[0]->rules[0]->rules);

        $this->assertEquals('foo', $context->segments[0]->rules[0]->rules[0]->conditions[0]->property);
        $this->assertEquals(SegmentConditionOperator::EQUAL, $context->segments[0]->rules[0]->rules[0]->conditions[0]->operator);
        $this->assertEquals('bar', $context->segments[0]->rules[0]->rules[0]->conditions[0]->value);

        $overrideKey = '1dfdec3e4c67121138b1faa01b82f9f731c692842b865f263824bfabf46d5fff';
        $this->assertArrayHasKey($overrideKey, $context->segments);
        $this->assertEquals('', $context->segments[$overrideKey]->key);
        $this->assertEquals('identity_overrides', $context->segments[$overrideKey]->name);
        $this->assertCount(1, $context->segments[$overrideKey]->rules);
        $this->assertCount(1, $context->segments[$overrideKey]->overrides);

        $this->assertEquals(SegmentRuleType::ALL, $context->segments[$overrideKey]->rules[0]->type);
        $this->assertCount(1, $context->segments[$overrideKey]->rules[0]->conditions);
        $this->assertNull($context->segments[$overrideKey]->rules[0]->rules);

        $this->assertEquals('$.identity.identifier', $context->segments[$overrideKey]->rules[0]->conditions[0]->property);
        $this->assertEquals(SegmentConditionOperator::IN, $context->segments[$overrideKey]->rules[0]->conditions[0]->operator);
        $this->assertEquals(['overridden-id'], $context->segments[$overrideKey]->rules[0]->conditions[0]->value);

        $this->assertEquals('', $context->segments[$overrideKey]->overrides[0]->key);
        $this->assertEquals('some_feature', $context->segments[$overrideKey]->overrides[0]->name);
        $this->assertFalse($context->segments[$overrideKey]->overrides[0]->enabled);
        $this->assertEquals('some-overridden-value', $context->segments[$overrideKey]->overrides[0]->value);
        $this->assertEquals(-INF, $context->segments[$overrideKey]->overrides[0]->priority);
        $this->assertNull($context->segments[$overrideKey]->overrides[0]->variants);
        $this->assertEquals(['flagsmith_id' => 1], $context->segments[$overrideKey]->overrides[0]->metadata);

        $this->assertCount(3, $context->features);
        $this->assertArrayHasKey('some_feature', $context->features);
        $this->assertEquals('00000000-0000-0000-0000-000000000000', $context->features['some_feature']->key);
        $this->assertEquals('some_feature', $context->features['some_feature']->name);
        $this->assertTrue($context->features['some_feature']->enabled);
        $this->assertEquals('some-value', $context->features['some_feature']->value);
        $this->assertNull($context->features['some_feature']->priority);
        $this->assertEmpty($context->features['some_feature']->variants);
        $this->assertEquals(['flagsmith_id' => 1], $context->features['some_feature']->metadata);

        // Test multivariate feature with IDs - priority should be based on ID
        $this->assertArrayHasKey('mv_feature_with_ids', $context->features);
        $mvFeatureWithIds = $context->features['mv_feature_with_ids'];
        $this->assertEquals('2', $mvFeatureWithIds->key);
        $this->assertEquals('mv_feature_with_ids', $mvFeatureWithIds->name);
        $this->assertTrue($mvFeatureWithIds->enabled);
        $this->assertEquals('default_value', $mvFeatureWithIds->value);
        $this->assertNull($mvFeatureWithIds->priority);
        $this->assertCount(2, $mvFeatureWithIds->variants);
        $this->assertEquals(['flagsmith_id' => 2], $mvFeatureWithIds->metadata);

        // First variant: ID=100, should have priority 100
        $this->assertEquals('variant_a', $mvFeatureWithIds->variants[0]->value);
        $this->assertEquals(30.0, $mvFeatureWithIds->variants[0]->weight);
        $this->assertEquals(100, $mvFeatureWithIds->variants[0]->priority);

        // Second variant: ID=200, should have priority 200
        $this->assertEquals('variant_b', $mvFeatureWithIds->variants[1]->value);
        $this->assertEquals(70.0, $mvFeatureWithIds->variants[1]->weight);
        $this->assertEquals(200, $mvFeatureWithIds->variants[1]->priority);

        // Test multivariate feature without IDs - priority should be based on UUID position
        $this->assertArrayHasKey('mv_feature_without_ids', $context->features);
        $mvFeatureWithoutIds = $context->features['mv_feature_without_ids'];
        $this->assertEquals('3', $mvFeatureWithoutIds->key);
        $this->assertEquals('mv_feature_without_ids', $mvFeatureWithoutIds->name);
        $this->assertFalse($mvFeatureWithoutIds->enabled);
        $this->assertEquals('fallback_value', $mvFeatureWithoutIds->value);
        $this->assertNull($mvFeatureWithoutIds->priority);
        $this->assertCount(3, $mvFeatureWithoutIds->variants);
        $this->assertEquals(['flagsmith_id' => 3], $mvFeatureWithoutIds->metadata);

        // Variants should be ordered by UUID alphabetically
        $this->assertEquals('option_y', $mvFeatureWithoutIds->variants[0]->value);
        $this->assertEquals(50.0, $mvFeatureWithoutIds->variants[0]->weight);
        $this->assertEquals(1, $mvFeatureWithoutIds->variants[0]->priority); // Second
        $this->assertEquals('option_x', $mvFeatureWithoutIds->variants[1]->value);
        $this->assertEquals(25.0, $mvFeatureWithoutIds->variants[1]->weight);
        $this->assertEquals(0, $mvFeatureWithoutIds->variants[1]->priority); // First
        $this->assertEquals('option_z', $mvFeatureWithoutIds->variants[2]->value);
        $this->assertEquals(25.0, $mvFeatureWithoutIds->variants[2]->weight);
        $this->assertEquals(2, $mvFeatureWithoutIds->variants[2]->priority); // Third
    }
}
