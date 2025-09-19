<?php
namespace FlagsmithTest\Engine\EngineTests;

use Flagsmith\Engine\Engine;
use Flagsmith\Engine\Utils\Types\Context\EvaluationContext;
use PHPUnit\Framework\TestCase;

class EngineDataTest extends TestCase
{
    private int $attempt = 0;

    /** @return array<array<mixed>> */
    public function extractTestCases()
    {
        $testDataContent = file_get_contents(
            __DIR__ .
                '/EngineTestData/data/environment_n9fbf9h3v4fFgH3U3ngWhb.json',
        );
        $testData = json_decode($testDataContent, associative: false);

        $parameters = [];
        foreach ($testData->test_cases as $testCase) {
            $context = EvaluationContext::fromJsonObject($testCase->context);
            $parameters[] = [$context, $testCase->result];
        }

        return $parameters;
    }

    /**
     * @dataProvider extractTestCases
     * @param EvaluationContext $evaluationContext
     * @param object $expectedEvaluationResult
     * @return void
     */
    public function testEngine(
        $evaluationContext,
        $expectedEvaluationResult,
    ): void {
        // When
        $evaluationResult = Engine::getEvaluationResult($evaluationContext);

        // Hack to allow comparing flags as associative arrays (<feature_name>: <flag>)
        $wanted = array_column($expectedEvaluationResult->flags, null, 'name');
        $expectedEvaluationResult->flags = $wanted;
        $actual = array_column($evaluationResult->flags, null, 'name');
        $evaluationResult->flags = $actual;

        // Then
        $this->assertEquals(
            json_decode(json_encode($expectedEvaluationResult), true),
            json_decode(json_encode($evaluationResult), true),
        );
    }
}
