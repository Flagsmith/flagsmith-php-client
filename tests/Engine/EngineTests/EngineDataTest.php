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
        $fileContents = file_get_contents(__DIR__ . '/EngineTestData/data/environment_n9fbf9h3v4fFgH3U3ngWhb.json');

        $contents = json_decode($fileContents);

        $parameters = [];
        foreach ($contents->test_cases as $testCase) {
            $context = EvaluationContext::fromJsonObject($testCase->context);
            $parameters[] = [$context, $testCase->response];
        }

        return $parameters;
    }

    /**
     * @dataProvider extractTestCases
     * @param EvaluationContext $evaluationContext
     * @param object $expectedResult
     * @return void
     */
    public function testEngine($evaluationContext, $expectedResult): void
    {
        // When
        $evaluationResult = Engine::getEvaluationResult($evaluationContext);

        // Then
        $this->assertEquals($expectedResult, $evaluationResult);
    }
}
