<?php

namespace Flagsmith\Engine\Features;

use Flagsmith\Concerns\HasWith;
use Flagsmith\Concerns\JsonSerializer;
use Flagsmith\Engine\Utils\UniqueUID;

class MultivariateFeatureStateValueModel
{
    use HasWith;
    use JsonSerializer;

    public ?int $id = null;
    public MultivariateFeatureOptionModel $multivariate_feature_option;
    public float $percentage_allocation;
    public string $mv_fs_value_uuid;

    private array $keys = [
        'multivariate_feature_option' => 'Flagsmith\Engine\Features\MultivariateFeatureOptionModel',
    ];

    public function __construct()
    {
        $this->mv_fs_value_uuid = UniqueUID::v4();
    }

    /**
     * get the ID.
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Build with ID.
     * @param int $id
     * @return MultivariateFeatureStateValueModel
     */
    public function withId(int $id): self
    {
        return $this->with('id', $id);
    }

    /**
     * get the multivariate option model.
     * @return MultivariateFeatureOptionModel
     */
    public function getMultivariateFeatureOption(): MultivariateFeatureOptionModel
    {
        return $this->multivariate_feature_option;
    }

    /**
     * Build with the multi variate feature option.
     * @param MultivariateFeatureOptionModel $multivariateFeatureOption
     * @return MultivariateFeatureStateValueModel
     */
    public function withMultivariateFeatureOption(MultivariateFeatureOptionModel $multivariateFeatureOption): self
    {
        return $this->with('multivariate_feature_option', $multivariateFeatureOption);
    }

    /**
     * get the percentage allocation.
     * @return float
     */
    public function getPercentageAllocation(): float
    {
        return $this->percentage_allocation;
    }

    /**
     * Build with percentage allocation.
     * @param float $percentageAllocation
     * @return MultivariateFeatureStateValueModel
     */
    public function withPercentageAllocation(float $percentageAllocation): self
    {
        return $this->with('percentage_allocation', $percentageAllocation);
    }

    /**
     * Get the multi variate feature state uuid.
     * @return string
     */
    public function getMvFsValueUuid(): string
    {
        return $this->mv_fs_value_uuid;
    }

    /**
     * Build with the multi variate feature state model.
     * @param string $mvFsValueUuid
     * @return MultivariateFeatureStateValueModel
     */
    public function withMvFsValueUuid(string $mvFsValueUuid): self
    {
        return $this->with('mv_fs_value_uuid', $mvFsValueUuid);
    }
}
