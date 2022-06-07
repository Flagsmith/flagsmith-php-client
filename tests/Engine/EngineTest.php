<?php

use Flagsmith\Engine\Engine;
use Flagsmith\Engine\Features\FeatureModel;
use Flagsmith\Engine\Features\FeatureStateModel;
use Flagsmith\Engine\Features\FeatureTypes;
use Flagsmith\Engine\Identities\Traits\TraitModel;
use Flagsmith\Engine\Utils\Collections\IdentityFeaturesList;
use Flagsmith\Engine\Utils\Exceptions\FeatureStateNotFound;
use FlagsmithTest\Engine\Fixtures;
use FlagsmithTest\Engine\Unit\Segments\SegmentFixtures;
use PHPUnit\Framework\TestCase;

class EngineTest extends TestCase
{
    public function testIdentityGetFeatureStateWithoutAnyOverride()
    {
        $feature1 = Fixtures::feature1();
        $featureState = Engine::getIdentityFeatureState(Fixtures::environment(), Fixtures::identity(), $feature1->getName());

        $this->assertEquals($featureState->getFeature(), $feature1);
    }

    public function testIdentityGetAllFeatureStatesNoSegments()
    {
        $environment = Fixtures::environment();
        $identity = Fixtures::identity();

        $overriddenFeature = (new FeatureModel())
            ->withId(3)
            ->withName('overridden_feature')
            ->withType(FeatureTypes::STANDARD);

        $environment->getFeatureStates()->append(
            (new FeatureStateModel())
            ->withDjangoId(3)
            ->withFeature($overriddenFeature)
            ->withEnabled(false)
        );

        $identity = $identity->withIdentityFeatures(
            new IdentityFeaturesList([
                (new FeatureStateModel())
                ->withDjangoId(4)
                ->withFeature($overriddenFeature)
                ->withEnabled(true)
            ])
        );

        $allFeaturesStates = Engine::getIdentityFeatureStates($environment, $identity);

        $this->assertEquals(count($allFeaturesStates), 3);

        foreach ($allFeaturesStates as $featuresState) {
            $environmentFeatureState = Engine::getEnvironmentFeatureState(
                $environment,
                $featuresState->getFeature()->getName()
            );

            $expected = $environmentFeatureState->getEnabled();
            if ($featuresState->getFeature() === $overriddenFeature) {
                $expected = true;
            }

            $this->assertEquals($featuresState->getEnabled(), $expected);
        }
    }

    public function testGetIdentityFeatureStatesHidesDisabledFlagsIfEnabled()
    {
        $environment = Fixtures::environment();
        $identity = Fixtures::identity();

        $environment = $environment
            ->withProject(
                $environment
                    ->getProject()
                    ->withHideDisabledFlags(true)
            );

        $featureStates = Engine::getIdentityFeatureStates($environment, $identity);

        foreach ($featureStates as $featureState) {
            $this->assertTrue($featureState->getEnabled());
        }
    }

    public function testEnvironmentGetAllFeatureStates()
    {
        $environment = Fixtures::environment();
        $featureStates = Engine::getEnvironmentFeatureStates($environment);
        foreach ($featureStates as $featureState) {
            $this->assertContains(
                $featureState,
                $environment->getFeatureStates()->getArrayCopy()
            );
        }
    }

    public function testEnvironmentGetFeatureState()
    {
        $environment = Fixtures::environment();
        $feature1 = Fixtures::feature1();

        $featureStates = Engine::getEnvironmentFeatureState($environment, $feature1->getName());

        $this->assertEquals($featureStates->getFeature(), $feature1);
    }

    public function testEnvironmentGetFeatureStateRaisesFeatureStateNotFound()
    {
        $this->expectException(FeatureStateNotFound::class);
        Engine::getEnvironmentFeatureState(Fixtures::environment(), 'not a name');
    }
}
