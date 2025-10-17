<?php

namespace FlagsmithTest\Engine\EngineTests;

use Flagsmith\Engine\Engine;
use Flagsmith\Engine\Utils\Types\Context\EvaluationContext;
use PHPUnit\Framework\TestCase;

class EngineDataTest extends TestCase
{
    private int $attempt = 0;

    /** @return \Generator<string, array<array<string, mixed>>> */
    public function extractTestCases(): \Generator
    {
        $testCasePaths = glob(__DIR__ . '/EngineTestData/test_cases/test_*.{json,jsonc}', \GLOB_BRACE);
        foreach ($testCasePaths as $testCasePath) {
            $testCaseJson = file_get_contents($testCasePath);
            $testCase = json5_decode($testCaseJson);

            $testName = basename($testCasePath);
            yield $testName => [[
                'context' => EvaluationContext::fromJsonObject($testCase->context),
                'result' => $testCase->result,
            ]];
        }
    }

    /**
     * @dataProvider extractTestCases
     * @param array<string, mixed> $case
     * @return void
     */
    public function testEngine($case): void
    {
        // When
        $result = Engine::getEvaluationResult($case['context']);

        // Then
        $this->assertEquals(
            json_decode(json_encode($case['result']), associative: true),
            json_decode(json_encode($result), associative: true),
        );
    }
}
