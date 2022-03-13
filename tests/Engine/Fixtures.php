<?php

namespace FlagsmithTest\Engine;

use Flagsmith\Engine\Environments\EnvironmentModel;
use Flagsmith\Engine\Features\FeatureModel;
use Flagsmith\Engine\Features\FeatureStateModel;
use Flagsmith\Engine\Organisations\OrganisationModel;
use Flagsmith\Engine\Projects\ProjectModel;
use Flagsmith\Engine\Segments\SegmentConditionModel;
use Flagsmith\Engine\Segments\SegmentConditions;
use Flagsmith\Engine\Segments\SegmentModel;
use Flagsmith\Engine\Segments\SegmentRuleModel;
use Flagsmith\Engine\Segments\SegmentRules;
use Flagsmith\Engine\Features\FeatureTypes;
use Flagsmith\Engine\Features\MultivariateFeatureOptionModel;
use Flagsmith\Engine\Features\MultivariateFeatureStateValueModel;
use Flagsmith\Engine\Identities\IdentityModel;
use Flagsmith\Engine\Identities\Traits\TraitModel;
use Flagsmith\Engine\Utils\Collections\FeatureStateModelList;
use Flagsmith\Engine\Utils\Collections\IdentityTraitList;
use Flagsmith\Engine\Utils\Collections\SegmentConditionModelList;
use Flagsmith\Engine\Utils\Collections\SegmentModelList;
use Flagsmith\Engine\Utils\Collections\SegmentRuleModelList;

class Fixtures
{
    private static string $segmentConditionProperty = 'foo';
    private static string $segmentConditionStringValue = 'foo';

    public static function segmentCondition()
    {
        $segmentConditionModel = new SegmentConditionModel();
        return $segmentConditionModel
            ->withOperator(SegmentConditions::EQUAL)
            ->withProperty(self::$segmentConditionProperty)
            ->withValue(self::$segmentConditionStringValue);
    }

    public static function segmentRule()
    {
        $segmentRuleModel = new SegmentRuleModel();
        return $segmentRuleModel
            ->withType(SegmentRules::ALL_RULE)
            ->withConditions(
                new SegmentConditionModelList(
                    [self::segmentCondition()]
                )
            );
    }

    public static function segment()
    {
        $segment = new SegmentModel();
        return $segment
            ->withId(1)
            ->withName('my_segment')
            ->withRules(
                new SegmentRuleModelList(
                    [self::segmentRule()]
                )
            );
    }

    public static function organisation()
    {
        $organisation = new OrganisationModel();
        return $organisation
            ->withId(1)
            ->withFeatureAnalytics(true)
            ->withName('test org')
            ->withStopServingFlags(false)
            ->withPersistTraitData(true);
    }

    public static function project()
    {
        $project = new ProjectModel();
        return $project
            ->withId(1)
            ->withOrganisation(self::organisation())
            ->withName('test project')
            ->withHideDisabledFlags(false)
            ->withSegments(
                new SegmentModelList([self::segment()])
            );
    }

    public static function feature1()
    {
        $featureModel = new FeatureModel();
        return $featureModel
            ->withId(1)
            ->withName('feature_1')
            ->withType(FeatureTypes::STANDARD);
    }

    public static function feature2()
    {
        $featureModel = new FeatureModel();
        return $featureModel
            ->withId(1)
            ->withName('feature_2')
            ->withType(FeatureTypes::STANDARD);
    }

    public static function environment()
    {
        $fsm1 = new FeatureStateModel();
        $fsm1 = $fsm1
            ->withDjangoId(1)
            ->withFeature(self::feature1())
            ->withEnabled(true);

        $fsm2 = new FeatureStateModel();
        $fsm2 = $fsm2
            ->withDjangoId(2)
            ->withFeature(self::feature2())
            ->withEnabled(false);

        $fsmList = new FeatureStateModelList([$fsm1, $fsm2]);

        $environment = new EnvironmentModel();
        return $environment
            ->withId(1)
            ->withApiKey('api-key')
            ->withProject(self::project())
            ->withFeatureStates($fsmList);
    }

    public static function identity()
    {
        $identityModel = new IdentityModel();
        return $identityModel
            ->withIdentifier('identifier_1')
            ->withEnvironmentApiKey(self::environment()->getApiKey())
            ->withCreatedDate(new \DateTime('now'));
    }

    public static function traitMatchingSegment()
    {
        $traitModel = new TraitModel();
        return $traitModel
            ->withTraitKey(self::segmentCondition()->getProperty())
            ->withTraitValue(self::segmentCondition()->getValue());
    }

    public static function identityInSegment()
    {
        $identityModel = new IdentityModel();
        return $identityModel
            ->withIdentifier('identifier_2')
            ->withEnvironmentApiKey(self::environment()->getApiKey())
            ->withIdentityTraits(new IdentityTraitList(
                [self::traitMatchingSegment()]
            ));
    }

    public static function segmentOverrideFs()
    {
        $fsm = new FeatureStateModel();
        $fsm = $fsm
            ->withDjangoId(4)
            ->withFeature(self::feature1())
            ->withEnabled(false);

        $fsm->setValue('segment_override');
        return $fsm;
    }

    public static function mvFeatureStateValue()
    {
        $multivariateFeatureOptionModel = new MultivariateFeatureOptionModel();
        $multivariateFeatureOptionModel = $multivariateFeatureOptionModel
            ->withId(1)
            ->withValue('test_value');

        $multivariateFeatureStateValueModel = new MultivariateFeatureStateValueModel();
        return $multivariateFeatureStateValueModel
            ->withId(1)
            ->withMultivariateFeatureOption($multivariateFeatureOptionModel)
            ->withPercentageAllocation(100);
    }

    public static function environmentWithSegmentOverride()
    {
        $segment = self::segment();
        $segmentOverride = self::segmentOverrideFs();
        $environment = self::environment();

        $segment->getFeatureStates()->append($segmentOverride);
        $environment->getProject()->getSegments()->append($segment);

        return $environment;
    }
}
