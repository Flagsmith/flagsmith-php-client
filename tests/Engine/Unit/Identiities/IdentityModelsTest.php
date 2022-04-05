<?php

use Flagsmith\Engine\Features\FeatureStateModel;
use Flagsmith\Engine\Identities\IdentityModel;
use Flagsmith\Engine\Identities\Traits\TraitModel;
use Flagsmith\Engine\Utils\Exceptions\DuplicateFeatureState;
use FlagsmithTest\Engine\Fixtures;
use PHPUnit\Framework\TestCase;

class IdentityModelsTest extends TestCase
{
    public function testCompositeKey()
    {
        $environmentKey = 'abc123';
        $identifier = 'identity';

        $identity = (new IdentityModel())
            ->withEnvironmentApiKey($environmentKey)
            ->withIdentifier($identifier);

        $this->assertEquals($identity->compositeKey(), "{$environmentKey}_{$identifier}");
    }

    public function testIdentiyModelCreatesDefaultIdentityUuid()
    {
        $identity = (new IdentityModel())
            ->withEnvironmentApiKey('environment_api')
            ->withIdentifier('some_key');

        $this->assertNotNull($identity->getIdentityUuid());
    }

    public function testGenerateCompositeKey()
    {
        $environmentKey = 'abc123';
        $identifier = 'identity';

        $this->assertEquals(
            IdentityModel::generateCompositeKey($environmentKey, $identifier),
            "{$environmentKey}_{$identifier}"
        );
    }

    public function testUpdateTraitsRemoveTraitsWithNoneValue()
    {
        $identityInSegment = Fixtures::identityInSegment();
        $traitKey = $identityInSegment->getIdentityTraits()[0]->getTraitKey();

        $this->assertEquals(
            count($identityInSegment->getIdentityTraits()->getArrayCopy()),
            1
        );

        $traitToRemove = (new TraitModel())->withTraitKey($traitKey)->withTraitValue(null);

        $identityInSegment->updateTraits([$traitToRemove]);

        $this->assertEquals(
            count($identityInSegment->getIdentityTraits()->getArrayCopy()),
            0
        );
    }

    public function testUpdateIdentityTraitsUpdatesTraitValue()
    {
        $identityInSegment = Fixtures::identityInSegment();
        $traitKey = $identityInSegment->getIdentityTraits()[0]->getTraitKey();
        $traitValue = 'update_trait_value';

        $traitToUpdate = (new TraitModel())
            ->withTraitKey($traitKey)
            ->withTraitValue($traitValue);

        $identityInSegment->updateTraits([$traitToUpdate]);

        $this->assertEquals(
            count($identityInSegment->getIdentityTraits()->getArrayCopy()),
            1
        );
        $this->assertEquals(
            $identityInSegment->getIdentityTraits()[0],
            $traitToUpdate
        );
    }

    public function testUpdateTraitsAddsNewTraits()
    {
        $identityInSegment = Fixtures::identityInSegment();
        $this->assertEquals(
            count($identityInSegment->getIdentityTraits()->getArrayCopy()),
            1
        );
        $traiModel = (new TraitModel())
            ->withTraitKey('newkey')
            ->withTraitValue('newvalue');

        $identityInSegment->updateTraits([$traiModel]);
        $this->assertEquals(
            count($identityInSegment->getIdentityTraits()->getArrayCopy()),
            2
        );
        $this->assertContains(
            $traiModel,
            $identityInSegment->getIdentityTraits()->getArrayCopy()
        );
    }

    public function testAppendingFeatureStatesRaisesDuplicateFeatureStateIfFsForTheFeatureAlreadyExists()
    {
        $identity = Fixtures::identity();
        $fs1 = (new FeatureStateModel())
            ->withFeature(Fixtures::feature1())
            ->withEnabled(false);
        $fs2 = (new FeatureStateModel())
            ->withFeature(Fixtures::feature1())
            ->withEnabled(true);

        $identity->getIdentityFeatures()->append($fs1);

        $this->expectException(DuplicateFeatureState::class);
        $identity->getIdentityFeatures()->append($fs2);
    }

    public function testAppendFeatureState()
    {
        $identity = Fixtures::identity();
        $fs1 = (new FeatureStateModel())
            ->withFeature(Fixtures::feature1())
            ->withEnabled(false);

        $identity->getIdentityFeatures()->append($fs1);

        $this->assertContains(
            $fs1,
            $identity->getIdentityFeatures()->getArrayCopy()
        );
    }
}
