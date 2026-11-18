<?php

namespace FlagsmithTest\Engine\Unit\Segments;

use Flagsmith\Engine\Engine;
use Flagsmith\Engine\Utils\Types\Context\EvaluationContext;
use PHPUnit\Framework\TestCase;

class RegexConditionTest extends TestCase
{
    /** @return array<string, array{string, string, bool}> */
    public function traitValuesAndPatterns(): array
    {
        return [
            'value starting with the pattern' => ['production', 'prod', true],
            'pattern found only mid-value' => ['nonproduction', 'prod', false],
            'value starting with any alternative' => ['development', 'prod|dev', true],
            'alternative found only mid-value' => ['nondev', 'prod|dev', false],
            'end-anchored pattern found only mid-value' => ['jane@example.com', '@example\.com$', false],
            'fully anchored email domain' => ['jane@example.com', '^[^@]+@example\.com$', true],
            'fully anchored environment prefix' => ['prod-api', '^(prod|uat|dev)-.+$', true],
            'fully anchored fixed-format ID' => ['user-0001', '^user-[0-9]{4}$', true],
            'fully anchored ID with trailing characters' => ['user-00012', '^user-[0-9]{4}$', false],
            'value differing in case' => ['Production', 'prod', false],
            'numeric trait value' => ['123', '^\d+$', true],
        ];
    }

    /**
     * @dataProvider traitValuesAndPatterns
     */
    public function testIdentityIsInSegmentWhenTraitMatchesRegexFromTheStart(
        string $traitValue,
        string $pattern,
        bool $expectedInSegment,
    ): void {
        // Given
        $context = EvaluationContext::fromJsonObject((object) [
            'environment' => (object) ['key' => 'api-key', 'name' => 'Test'],
            'identity' => (object) [
                'identifier' => 'jane',
                'traits' => (object) ['environment' => $traitValue],
            ],
            'segments' => [
                (object) [
                    'key' => '1',
                    'name' => 'regex_segment',
                    'rules' => [
                        (object) [
                            'type' => 'ALL',
                            'conditions' => [
                                (object) [
                                    'property' => 'environment',
                                    'operator' => 'REGEX',
                                    'value' => $pattern,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // When
        $result = Engine::getEvaluationResult($context);

        // Then
        $this->assertSame(
            $expectedInSegment ? ['regex_segment'] : [],
            array_map(fn ($segment) => $segment->name, $result->segments),
        );
    }
}
