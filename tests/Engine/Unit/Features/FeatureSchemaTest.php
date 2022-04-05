<?php

use Flagsmith\Engine\Features\FeatureStateModel;
use Flagsmith\Engine\Features\MultivariateFeatureOptionModel;
use Flagsmith\Engine\Features\MultivariateFeatureStateValueModel;
use Flagsmith\Engine\Utils\Exceptions\InvalidPercentageAllocation;
use PHPUnit\Framework\TestCase;

class FeatureSchemaTest extends TestCase
{
    public function testCanLoadMultivariateFeatureOptionDictWithoutIdField()
    {
        MultivariateFeatureOptionModel::build([ 'value' => 1]);
        // validation in with()
        $this->assertTrue(true);
    }

    public function testCanLoadMultivariateFeatureStateValueWithoutIdField()
    {
        MultivariateFeatureStateValueModel::build([
            'multivariate_feature_option' => ['value' => 1],
            'percentage_allocation' => 10
        ]);
        // validation in with()
        $this->assertTrue(true);
    }

    public function testDumpingFsSchemaRaisesInvalidPercentageAllocationForInvalidAllocation()
    {
        $mvFsValue1 = MultivariateFeatureStateValueModel::build([
            'multivariate_feature_option' => ['value' => 12],
            'percentage_allocation' => 100
        ]);
        $mvFsValue2 = MultivariateFeatureStateValueModel::build([
            'multivariate_feature_option' => ['value' => 9],
            'percentage_allocation' => 80
        ]);

        $fsm = new FeatureStateModel();
        $fsm->getMultivariateFeatureStateValues()->append($mvFsValue1);
        $fsm->getMultivariateFeatureStateValues()->append($mvFsValue2);

        $this->expectException(InvalidPercentageAllocation::class);
        json_encode($fsm);
    }

    public function testDumpingFsSchemaWorksForValidAllocation()
    {
        $mvFsValue1 = MultivariateFeatureStateValueModel::build([
            'multivariate_feature_option' => ['value' => 12],
            'percentage_allocation' => 80
        ]);
        $mvFsValue2 = MultivariateFeatureStateValueModel::build([
            'multivariate_feature_option' => ['value' => 9],
            'percentage_allocation' => 20
        ]);

        $fsm = new FeatureStateModel();
        $fsm->getMultivariateFeatureStateValues()->append($mvFsValue1);
        $fsm->getMultivariateFeatureStateValues()->append($mvFsValue2);

        json_encode($fsm);
        $this->assertTrue(true);
    }
}
