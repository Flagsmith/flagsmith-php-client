<?php

namespace Flagsmith\Models;

use Flagsmith\Concerns\HasWith;

class Flag extends BaseFlag
{
    public int $feature_id;
    public string $feature_name;

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
            ->withValue($flagDict->feature_state_value);
    }
}
