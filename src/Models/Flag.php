<?php

namespace Flagsmith\Models;

use Flagsmith\Concerns\HasWith;

class Flag
{
    use HasWith;

    private int $id;
    private Feature $feature;
    private string $featureStateValue;
    private bool $enabled;
    private int $environment;
    private ?int $featureSegment = null;

    /**
     * Get the value of id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @param int $id
     *
     * @return self
     */
    public function withId(int $id): self
    {
        return $this->with('id', $id);
    }

    /**
     * Get the value of feature
     *
     * @return Feature
     */
    public function getFeature(): Feature
    {
        return $this->feature;
    }

    /**
     * Set the value of feature
     *
     * @param Feature $feature
     *
     * @return self
     */
    public function withFeature(Feature $feature): self
    {
        return $this->with('feature', $feature);
    }

    /**
     * Get the value of featureStateValue
     *
     * @return string
     */
    public function getFeatureStateValue(): string
    {
        return $this->featureStateValue;
    }

    /**
     * Set the value of featureStateValue
     *
     * @param string $featureStateValue
     *
     * @return self
     */
    public function withFeatureStateValue(string $featureStateValue): self
    {
        return $this->with('featureStateValue', $featureStateValue);
    }

    /**
     * Get the value of enabled
     *
     * @return bool
     */
    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Set the value of enabled
     *
     * @param bool $enabled
     *
     * @return self
     */
    public function withEnabled(bool $enabled): self
    {
        return $this->with('enabled', $enabled);
    }

    /**
     * Get the value of environment
     *
     * @return int
     */
    public function getEnvironment(): int
    {
        return $this->environment;
    }

    /**
     * Set the value of environment
     *
     * @param int $environment
     *
     * @return self
     */
    public function withEnvironment(int $environment): self
    {
        return $this->with('environment', $environment);
    }

    /**
     * Get the value of featureSegment
     *
     * @return ?int
     */
    public function getFeatureSegment(): ?int
    {
        return $this->featureSegment;
    }

    /**
     * Set the value of featureSegment
     *
     * @param int|null $featureSegment
     *
     * @return self
     */
    public function withFeatureSegment(?int $featureSegment): self
    {
        return $this->with('featureSegment', $featureSegment);
    }
}
