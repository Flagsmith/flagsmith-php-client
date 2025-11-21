<?php

namespace FlagsmithTest\Utils;

use Flagsmith\Utils\Mappers;
use PHPUnit\Framework\TestCase;

class MappersTest extends TestCase
{
    /** @return \Generator<string, array<array<string, mixed>>> */
    public function extractMapperTestCases(): \Generator
    {
        $testCasePaths = glob(__DIR__ . '/../Engine/EngineTests/EngineTestData/mapper_test_cases/test_*.{json,jsonc}', \GLOB_BRACE);
        foreach ($testCasePaths as $testCasePath) {
            $testCaseJson = file_get_contents($testCasePath);
            $testCase = json5_decode($testCaseJson);

            $testName = basename($testCasePath);
            yield $testName => [[
                'environment_document' => $testCase->environment_document,
                'expected_evaluation_context' => $testCase->expected_evaluation_context,
            ]];
        }
    }

    /**
     * @dataProvider extractMapperTestCases
     * @param array<string, mixed> $case
     * @return void
     */
    public function testMapEnvironmentDocumentToContextMatchesTestData($case): void
    {
        // Given
        $environmentDocument = $case['environment_document'];

        // When
        $actual = Mappers::mapEnvironmentDocumentToContext($environmentDocument);

        // Replace -INF with string "-INF" to allow JSON encoding
        $serialized = serialize($actual);
        $serialized = str_replace('d:-INF;', 's:4:"-INF";', $serialized);
        $actual = unserialize($serialized);

        // Then
        $this->assertEquals(
            $case['expected_evaluation_context'],
            json_decode(json_encode($actual)),
        );
    }
}
