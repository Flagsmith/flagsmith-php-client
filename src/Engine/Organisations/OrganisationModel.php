<?php

namespace Flagsmith\Engine\Organisations;

use Flagsmith\Concerns\HasWith;
use Flagsmith\Concerns\JsonSerializer;

class OrganisationModel
{
    use HasWith;
    use JsonSerializer;

    public bool $persist_trait_data;
    public bool $stop_serving_flags;
    public bool $feature_analytics;
    public string $name;
    public int $id;
    private array $keys = [];

    /**
     * get the id.
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Build with ID.
     * @param int $id
     * @return OrganisationModel
     */
    public function withId(int $id): self
    {
        return $this->with('id', $id);
    }

    /**
     * Get the name.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Build with name.
     * @param string $name
     * @return OrganisationModel
     */
    public function withName(string $name): self
    {
        return $this->with('name', $name);
    }

    /**
     * Get the feature Analytics.
     * @return bool
     */
    public function getFeatureAnalytics(): bool
    {
        return $this->feature_analytics;
    }

    /**
     * Build with feature analytics.
     * @param bool $featureAnalytics
     * @return OrganisationModel
     */
    public function withFeatureAnalytics(bool $featureAnalytics): self
    {
        return $this->with('feature_analytics', $featureAnalytics);
    }

    /**
     * Get the stop serving flags bool.
     * @return bool
     */
    public function getStopServingFlags(): bool
    {
        return $this->stop_serving_flags;
    }

    /**
     * Buld with Stop serving flags.
     * @param bool $stopServingFlags
     * @return OrganisationModel
     */
    public function withStopServingFlags(bool $stopServingFlags): self
    {
        return $this->with('stop_serving_flags', $stopServingFlags);
    }

    /**
     * get the persist trait data.
     * @return bool
     */
    public function getPersistTraitData(): bool
    {
        return $this->persist_trait_data;
    }

    /**
     * Build with persist trait data.
     * @param bool $persistTraitData
     * @return OrganisationModel
     */
    public function withPersistTraitData(bool $persistTraitData): self
    {
        return $this->with('persist_trait_data', $persistTraitData);
    }

    /**
     * Get the unique slug.
     * @return string
     */
    public function uniqueSlug(): string
    {
        return "{$this->id}-{$this->name}";
    }
}
