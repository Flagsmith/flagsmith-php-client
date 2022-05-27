<?php

use Flagsmith\Engine\Features\FeatureModel;
use Flagsmith\Engine\Features\FeatureSegmentModel;
use Flagsmith\Engine\Features\FeatureStateModel;
use Flagsmith\Engine\Features\FeatureTypes;
use Flagsmith\Engine\Features\MultivariateFeatureOptionModel;
use Flagsmith\Engine\Features\MultivariateFeatureStateValueModel;
use Flagsmith\Engine\Utils\Collections\MultivariateFeatureStateValueModelList;
use Flagsmith\Engine\Utils\Hashing;
use FlagsmithTest\Engine\Fixtures;
use PHPUnit\Framework\TestCase;

class FeatureModelsTest extends TestCase
{
    public function testInitializingFeatureStateCreatesDefaultFeatureStateUuid()
    {
        $featureState = new FeatureStateModel();

        $featureState = $featureState
            ->withDjangoId(1)
            ->withFeature(Fixtures::feature1())
            ->withEnabled(true);

        $this->assertNotNull($featureState->getFeaturestateUuid());
    }

    public function testInitializingMultivariateFeatureStateValueCreatesDefaultUuid()
    {
        $mvFeatureOption = (new MultivariateFeatureOptionModel())
            ->withValue('value');
        $mvFsValueModel = (new MultivariateFeatureStateValueModel())
            ->withMultivariateFeatureOption($mvFeatureOption)
            ->withId(1)
            ->withPercentageAllocation(10);

        $this->assertNotNull($mvFsValueModel->getMvFsValueUuid());
    }
    public function testFeatureStateGetValueNoMvValues()
    {
        $value = 'foo';
        $featureState = (new FeatureStateModel())
            ->withDjangoId(1)
            ->withFeature(Fixtures::feature1())
            ->withEnabled(true);
        $featureState->setValue($value);

        $this->assertEquals($featureState->getValue(), $featureState->getValue(1));
        $this->assertEquals($featureState->getValue(1), $value);
    }

    public function dataForFeatureState(): array
    {
        return [
            [10, 'foo'],
            [40, 'bar'],
            [70, 'control']
        ];
    }

    /**
     * @dataProvider  dataForFeatureState
     */
    public function testFeatureStateGetValueMvValues(int $percentage, string $expectedValue)
    {
        $myFeature = (new FeatureModel())
            ->withId(1)
            ->withName('mv_feature')
            ->withType(FeatureTypes::STANDARD);

        $mvFeatureOption1 = (new MultivariateFeatureOptionModel())
            ->withId(1)
            ->withValue('foo');
        $mvFeatureOption2 = (new MultivariateFeatureOptionModel())
            ->withId(2)
            ->withValue('bar');

        $mvFeatureStateValue1 = (new MultivariateFeatureStateValueModel())
            ->withId(1)
            ->withMultivariateFeatureOption($mvFeatureOption1)
            ->withPercentageAllocation(30);
        $mvFeatureStateValue2 = (new MultivariateFeatureStateValueModel())
            ->withId(2)
            ->withMultivariateFeatureOption($mvFeatureOption2)
            ->withPercentageAllocation(30);

        $mvFeatureState = (new FeatureStateModel())
            ->withDjangoId(1)
            ->withFeature($myFeature)
            ->withEnabled(true)
            ->withMultivariateFeatureStateValues(
                new MultivariateFeatureStateValueModelList(
                    [$mvFeatureStateValue1, $mvFeatureStateValue2]
                )
            );
        $mvFeatureState->setValue('control');

        $hashingStub = $this->createMock(Hashing::class);
        $hashingStub
            ->method('getHashedPercentageForObjectIds')
            ->will($this->returnValue($percentage));
        FeatureStateModel::setHashObject($hashingStub);

        $this->assertEquals($mvFeatureState->getValue(1), $expectedValue);
    }

    public function testGetValueUsesDjangoIdForMultivariateValueCalculationIfNotNone()
    {
        $hashingStub = $this->createMock(Hashing::class);
        $hashingStub
            ->method('getHashedPercentageForObjectIds')
            ->with($this->identicalTo([1, 1]))
            ->will($this->returnValue(10));
        FeatureStateModel::setHashObject($hashingStub);

        $featureState = (new FeatureStateModel())
            ->withDjangoId(1)
            ->withFeature(Fixtures::feature1())
            ->withEnabled(true)
            ->withMultivariateFeatureStateValues(new MultivariateFeatureStateValueModelList(
                [Fixtures::mvFeatureStateValue()]
            ));
        $featureState->getValue(1);
        // validation in with()
        $this->assertTrue(true);
    }

    public function testGetValueUsesFeatuestateUuidForMultivariateValueCalculationIfDjangoIdIsNotPresent()
    {
        $featureState = (new FeatureStateModel())
            ->withFeature(Fixtures::feature1())
            ->withEnabled(true)
            ->withMultivariateFeatureStateValues(new MultivariateFeatureStateValueModelList(
                [Fixtures::mvFeatureStateValue()]
            ));

        $hashingStub = $this->createMock(Hashing::class);
        $hashingStub
            ->method('getHashedPercentageForObjectIds')
            ->with($this->identicalTo(
                [$featureState->getFeaturestateUuid(), 1]
            ))
            ->will($this->returnValue(10));
        FeatureStateModel::setHashObject($hashingStub);

        $featureState->getValue(1);
        // validation in with()
        $this->assertTrue(true);
    }

    public function testIsHigherPriorityReturnsFalseForTwoNullFeatureSegments()
    {
        // Given
        $featureState1 = (new FeatureStateModel())
            ->withFeatureSegment(null);
        $featureState2 = (new FeatureStateModel())
            ->withFeatureSegment(null);

        // Then
        $this->assertFalse($featureState1->isHigherPriority($featureState2));
        $this->assertFalse($featureState2->isHigherPriority($featureState1));
    }

    public function testIsHigherPriorityReturnsTrueWhenOtherFeatureStateHasNullFeatureSegment()
    {
        // Given
        $featureState1 = (new FeatureStateModel())
            ->withFeatureSegment((new FeatureSegmentModel())->withPriority(0));
        $featureState2 = (new FeatureStateModel())
            ->withFeatureSegment(null);

        // Then
        $this->assertTrue($featureState1->isHigherPriority($featureState2));
    }

    public function testIsHigherPriorityReturnsTrueWhenOtherFeatureStateHasLowerPriorityFeatureSegment()
    {
        // Given
        $featureState1 = (new FeatureStateModel())
            ->withFeatureSegment((new FeatureSegmentModel())->withPriority(0));
        $featureState2 = (new FeatureStateModel())
            ->withFeatureSegment((new FeatureSegmentModel())->withPriority(1));

        // Then
        $this->assertTrue($featureState1->isHigherPriority($featureState2));
    }
}
