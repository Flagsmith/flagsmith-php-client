<?php

namespace Flagsmith\Engine\Features;

use Flagsmith\Concerns\HasWith;
use Flagsmith\Concerns\JsonSerializer;
use Flagsmith\Engine\Utils\Collections\MultivariateFeatureStateValueModelList;
use Flagsmith\Engine\Utils\HashingTrait;
use Flagsmith\Engine\Utils\UniqueUID;

#[\AllowDynamicProperties]
class FeatureStateModel
{
    use HasWith, HashingTrait, JsonSerializer {
        JsonSerializer::setValues as setValuesSerializer;
    }

    public FeatureModel $feature;
    public bool $enabled;
    public mixed $feature_state_value;
    public string $featurestate_uuid;
    public MultivariateFeatureStateValueModelList $multivariate_feature_state_values;
    public ?int $django_id = null;
    public ?FeatureSegmentModel $feature_segment = null;

    private array $keys = [
        'feature' => 'Flagsmith\Engine\Features\FeatureModel',
        'multivariate_feature_state_values' => 'Flagsmith\Engine\Utils\Collections\MultivariateFeatureStateValueModelList',
        'feature_segment' => 'Flagsmith\Engine\Features\FeatureSegmentModel',
    ];

    public function __construct()
    {
        $this->featurestate_uuid = UniqueUID::v4();
        $this->multivariate_feature_state_values = new MultivariateFeatureStateValueModelList();
    }

    /**
     * Get the django ID.
     * @return int
     */
    public function getDjangoId(): int
    {
        return $this->django_id;
    }

    /**
     * Build with django ID.
     * @param int $djangoId
     * @return FeatureStateModel
     */
    public function withDjangoId(int $djangoId): self
    {
        return $this->with('django_id', $djangoId);
    }

    /**
     * get the feature model.
     * @return FeatureModel
     */
    public function getFeature(): FeatureModel
    {
        return $this->feature;
    }

    /**
     * build with the feature model.
     * @param FeatureModel $feature
     * @return FeatureStateModel
     */
    public function withFeature(FeatureModel $feature): self
    {
        return $this->with('feature', $feature);
    }

    /**
     * get enabled value.
     * @return bool
     */
    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * build with enabled.
     * @param bool $enabled
     * @return FeatureStateModel
     */
    public function withEnabled(bool $enabled): self
    {
        return $this->with('enabled', $enabled);
    }

    /**
     * get the multivariate feature state value.
     * @return MultivariateFeatureStateValueModelList
     */
    public function getMultivariateFeatureStateValues(): MultivariateFeatureStateValueModelList
    {
        return $this->multivariate_feature_state_values;
    }

    /**
     * build with multi variate feature state value.
     * @param MultivariateFeatureStateValueModelList $multivariateFeatureStateValues
     * @return FeatureStateModel
     */
    public function withMultivariateFeatureStateValues(MultivariateFeatureStateValueModelList $multivariateFeatureStateValues): self
    {
        return $this->with('multivariate_feature_state_values', $multivariateFeatureStateValues);
    }

    /**
     * get the feature state uuid.
     * @return string
     */
    public function getFeaturestateUuid(): string
    {
        return $this->featurestate_uuid;
    }

    /**
     * get the feature state uuid.
     * @param string $featurestateUuid
     * @return FeatureStateModel
     */
    public function withFeaturestateUuid(string $featurestateUuid): self
    {
        return $this->with('featurestate_uuid', $featurestateUuid);
    }

    /**
     * get the feature ssegment.
     * @return FeatureSegmentModel
     */
    public function getFeatureSegment(): ?FeatureSegmentModel
    {
        return $this->feature_segment;
    }

    /**
     * build with a feature segment model.
     * @param FeatureSegmentModel $featureSegment
     * @return FeatureStateModel
     */
    public function withFeatureSegment(?FeatureSegmentModel $featureSegment): self
    {
        return $this->with('feature_segment', $featureSegment);
    }

    /**
     * get the value.
     */
    public function getValue($identityId = null)
    {
        if ($identityId && count($this->multivariate_feature_state_values) > 0) {
            return $this->getMultivariateValue($identityId);
        }
        return $this->feature_state_value;
    }

    /**
     * Get the feature statue value.
     */
    public function getFeatureStateValue()
    {
        return $this->getValue();
    }

    /**
     * get the value from multi variate configuration.
     * @param mixed $identityId
     * @return mixed
     */
    private function getMultivariateValue($identityId)
    {
        $identityIdArray = [
            $this->django_id ?? $this->featurestate_uuid,
            $identityId
        ];

        $percentageValue = $this->getHashedPercentageForObjectIds($identityIdArray);

        $startPercentage = 0;
        $sortedMvFeatureStateValues = $this
            ->getMultivariateFeatureStateValues()
            ->getArrayCopy();

        usort(
            $sortedMvFeatureStateValues,
            function (
                MultivariateFeatureStateValueModel $mvFeatureState1,
                MultivariateFeatureStateValueModel $mvFeatureState2
            ) {
                $id1 = $mvFeatureState1->getId() ?? $mvFeatureState1->getMvFsValueUuid();
                $id2 = $mvFeatureState2->getId() ?? $mvFeatureState2->getMvFsValueUuid();
                return $id1 <=> $id2;
            }
        );

        foreach ($sortedMvFeatureStateValues as $mvValue) {
            $limit = $mvValue->getPercentageAllocation() + $startPercentage;
            if ($startPercentage <= $percentageValue && $percentageValue < $limit) {
                return $mvValue->getMultivariateFeatureOption()->getValue();
            }
            $startPercentage = $limit;
        }

        return $this->feature_state_value;
    }

    /**
     * set the value.
     * @param mixed $value
     * @return void
     */
    public function setValue($value)
    {
        $this->feature_state_value = $value;
    }


    /**
     * Set values from keys.
     * @param mixed $values
     * @return void
     */
    protected function setValues($values)
    {
        $featureStateValue = $values->feature_state_value;
        unset($values->feature_state_value);
        $this->setValuesSerializer($values);
        if (!empty($featureStateValue)) {
            $this->feature_state_value = $featureStateValue;
        }
    }

    /**
     * Another FeatureStateModel is deemed to be higher priority if and only if
     * it has a FeatureSegment and either this.FeatureSegment is null or the
     * value of other.FeatureSegment.priority is lower than that of
     * this.FeatureSegment.priority.
     *
     * @param FeatureStateModel $other - the other FeatureStateModel to compare priority with
     * @return bool - true if `this` is higher priority than `other`
     */
    public function isHigherPriority(FeatureStateModel $other): bool
    {
        if ($this->getFeatureSegment() == null || $other->getFeatureSegment() == null) {
            return ($this->getFeatureSegment() != null && $other->getFeatureSegment() == null);
        }

        return $this->getFeatureSegment()->getPriority() < $other->getFeatureSegment()->getPriority();
    }
}
