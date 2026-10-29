<?php

namespace Flagsmith\Models;

use Flagsmith\Concerns\HasWith;

class Flag extends BaseFlag
{
    public int $feature_id;
    public string $feature_name;
    public ?string $variant = null;
    public ?Experiment $experiment = null;

    /**
     * Get the Feature Name
     * @return string
     */
    public function getFeatureName(): string
    {
        return $this->feature_name;
    }

    /**
     * Build the Feature Name
     * @param string $featureName
     * @return Flag
     */
    public function withFeatureName(string $featureName): self
    {
        return $this->with('feature_name', $featureName);
    }

    /**
     * Get the feature ID.
     * @return int
     */
    public function getFeatureId(): int
    {
        return $this->feature_id;
    }

    /**
     * Build with the feature ID.
     * @param int $featureId
     * @return Flag
     */
    public function withFeatureId(int $featureId): self
    {
        return $this->with('feature_id', $featureId);
    }

    /**
     * Get the key of the multivariate option the flag was served, if any.
     * @return string|null
     */
    public function getVariant(): ?string
    {
        return $this->variant;
    }

    /**
     * Build with the variant key.
     * @param string|null $variant
     * @return Flag
     */
    public function withVariant(?string $variant): self
    {
        return $this->with('variant', $variant);
    }

    /**
     * Get the running experiment the flag was evaluated under, if any.
     * @return Experiment|null
     */
    public function getExperiment(): ?Experiment
    {
        return $this->experiment;
    }

    /**
     * Build with the experiment.
     * @param Experiment|null $experiment
     * @return Flag
     */
    public function withExperiment(?Experiment $experiment): self
    {
        return $this->with('experiment', $experiment);
    }

    /**
     * Build flag from dict.
     * @param object $flagDict
     * @return Flag
     */
    public static function fromApiFlag(object $flagDict): self
    {
        return (new self())
            ->withFeatureId($flagDict->feature->id)
            ->withFeatureName($flagDict->feature->name)
            ->withEnabled($flagDict->enabled)
            ->withValue($flagDict->feature_state_value)
            ->withVariant($flagDict->variant ?? null)
            ->withExperiment(Experiment::fromApiMetadata($flagDict->metadata ?? null));
    }
}
