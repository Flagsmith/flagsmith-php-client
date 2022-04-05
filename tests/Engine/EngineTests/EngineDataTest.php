<?php

use Flagsmith\Engine\Engine;
use Flagsmith\Engine\Environments\EnvironmentModel;
use Flagsmith\Engine\Identities\IdentityModel;
use PHPUnit\Framework\TestCase;

class EngineDataTest extends TestCase
{
    private int $attempt = 0;
    public function extractTestCases()
    {
        $fileContents = file_get_contents(__DIR__ . '/EngineTestData/data/environment_n9fbf9h3v4fFgH3U3ngWhb.json');

        $contents = json_decode($fileContents);

        $environmentModel = EnvironmentModel::build($contents->environment);

        $parameters = [];
        foreach ($contents->identities_and_responses as $testCase) {
            $parameters[] = [
                $environmentModel,
                IdentityModel::build($testCase->identity),
                $testCase->response
            ];
        }

        return $parameters;
    }

    /**
     * @dataProvider extractTestCases
     */
    public function testEngine($environmentModel, $identityModel, $expectedResponse)
    {
        $this->attempt++;
        $engineResponse = Engine::getIdentityFeatureStates($environmentModel, $identityModel);

        usort(
            $engineResponse,
            fn ($fs1, $fs2) => $fs1->getFeature()->getName() <=> $fs2->getFeature()->getName()
        );

        $flags = $expectedResponse->flags;
        usort(
            $flags,
            fn ($fs1, $fs2) => $fs1->feature->name <=> $fs2->feature->name
        );

        $this->assertEquals(
            count($flags),
            count($engineResponse)
        );

        foreach ($engineResponse as $index => $featureState) {
            $val = $featureState->getValue($identityModel->getDjangoId());
            $expectedVal = $flags[$index]->feature_state_value;

            $this->assertEquals(
                $val,
                $expectedVal
            );
            $this->assertEquals(
                count($flags),
                count($engineResponse)
            );
        }
    }
}
