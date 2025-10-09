<?php

namespace FlagsmithTest\Utils;

use Flagsmith\Engine\Utils\Types\Context\EnvironmentContext;
use Flagsmith\Engine\Utils\Types\Context\EvaluationContext;
use Flagsmith\Engine\Utils\Types\Context\SegmentRuleType;
use Flagsmith\Engine\Utils\Types\Context\SegmentConditionOperator;
use Flagsmith\Utils\Mappers;
use FlagsmithTest\DataFixtures;
use PHPUnit\Framework\TestCase;

class MappersTest extends TestCase
{
    public function testMapEnvironmentDocumentToContextProducesEvaluationContext(): void
    {
        // Given
        $environmentDocument = json_decode(
            json: DataFixtures::loadFileContents('environment.json'),
            associative: false,
        );

        // When
        $context = Mappers::mapEnvironmentDocumentToContext($environmentDocument);

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
        $this->assertEquals($overrideKey, $context->segments[$overrideKey]->key);
        $this->assertEquals('identity_overrides', $context->segments[$overrideKey]->name);
        $this->assertCount(1, $context->segments[$overrideKey]->rules);
        $this->assertCount(1, $context->segments[$overrideKey]->overrides);

        $this->assertEquals(SegmentRuleType::ALL, $context->segments[$overrideKey]->rules[0]->type);
        $this->assertCount(1, $context->segments[$overrideKey]->rules[0]->conditions);
        $this->assertNull($context->segments[$overrideKey]->rules[0]->rules);

        $this->assertEquals('$.identity.identifier', $context->segments[$overrideKey]->rules[0]->conditions[0]->property);
        $this->assertEquals(SegmentConditionOperator::IN, $context->segments[$overrideKey]->rules[0]->conditions[0]->operator);
        $this->assertEquals(['overridden-id'], $context->segments[$overrideKey]->rules[0]->conditions[0]->value);

        $this->assertEquals('--irrelevant--', $context->segments[$overrideKey]->overrides[0]->key);
        $this->assertEquals(1, $context->segments[$overrideKey]->overrides[0]->feature_key);
        $this->assertEquals('some_feature', $context->segments[$overrideKey]->overrides[0]->name);
        $this->assertFalse($context->segments[$overrideKey]->overrides[0]->enabled);
        $this->assertEquals('some-overridden-value', $context->segments[$overrideKey]->overrides[0]->value);
        $this->assertEquals(-INF, $context->segments[$overrideKey]->overrides[0]->priority);
        $this->assertNull($context->segments[$overrideKey]->overrides[0]->variants);

        $this->assertCount(1, $context->features);
        $this->assertArrayHasKey('some_feature', $context->features);
        $this->assertEquals('40eb539d-3713-4720-bbd4-829dbef10d51', $context->features['some_feature']->key);
        $this->assertEquals('1', $context->features['some_feature']->feature_key);
        $this->assertEquals('some_feature', $context->features['some_feature']->name);
        $this->assertTrue($context->features['some_feature']->enabled);
        $this->assertEquals('some-value', $context->features['some_feature']->value);
        $this->assertNull($context->features['some_feature']->priority);
        $this->assertEmpty($context->features['some_feature']->variants);
    }

    public function testMapContextAndIdentityToContextProducesEvaluationContextWithIdentity(): void
    {
        // Given
        $originalContext = new EvaluationContext();
        $originalContext->environment = new EnvironmentContext();
        $originalContext->environment->key = 'public-env-key';

        // When
        $context = Mappers::mapContextAndIdentityToContext(
            context: $originalContext,
            identifier: 'neo',
            traits: (object) [
                'chosen-pill' => 'red',
                'has-met-the-oracle' => true,
            ],
        );

        // Then
        $this->assertInstanceOf(EvaluationContext::class, $context);
        $this->assertNotSame($originalContext, $context);
        $this->assertEquals('public-env-key', $context->environment->key);
        $this->assertEquals('neo', $context->identity->identifier);
        $this->assertEquals('public-env-key_neo', $context->identity->key);
        $this->assertCount(2, $context->identity->traits);
        $this->assertEquals('red', $context->identity->traits['chosen-pill']);
        $this->assertTrue($context->identity->traits['has-met-the-oracle']);
    }
}
