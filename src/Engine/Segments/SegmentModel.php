<?php

namespace Flagsmith\Engine\Segments;

use Flagsmith\Concerns\HasWith;
use Flagsmith\Concerns\JsonSerializer;
use Flagsmith\Engine\Utils\Collections\FeatureStateModelList;
use Flagsmith\Engine\Utils\Collections\SegmentRuleModelList;

#[\AllowDynamicProperties]
class SegmentModel
{
    use HasWith;
    use JsonSerializer;
    public int $id;
    public string $name;
    public SegmentRuleModelList $rules;
    public FeatureStateModelList $feature_states;
    private array $keys = [
        'rules' => 'Flagsmith\Engine\Utils\Collections\SegmentRuleModelList',
        'feature_states' => 'Flagsmith\Engine\Utils\Collections\FeatureStateModelList',
    ];

    public function __construct()
    {
        $this->rules = new SegmentRuleModelList();
        $this->feature_states = new FeatureStateModelList();
    }

    /**
     * get the feature states.
     * @return FeatureStateModelList
     */
    public function getFeatureStates(): FeatureStateModelList
    {
        return $this->feature_states;
    }

    /**
     * build with feature states.
     * @param FeatureStateModelList $featureStates
     * @return SegmentModel
     */
    public function withFeatureStates(FeatureStateModelList $featureStates): self
    {
        return $this->with('feature_states', $featureStates);
    }

    /**
     * get the rules.
     * @return SegmentRuleModelList
     */
    public function getRules(): SegmentRuleModelList
    {
        return $this->rules;
    }

    /**
     * build with rules.
     * @param SegmentRuleModelList $rules
     * @return SegmentModel
     */
    public function withRules(SegmentRuleModelList $rules): self
    {
        return $this->with('rules', $rules);
    }

    /**
     * get the name.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Build with name.
     * @param string $name
     * @return SegmentModel
     */
    public function withName(string $name): self
    {
        return $this->with('name', $name);
    }

    /**
     * Get the ID.
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Build with ID.
     * @param int $id
     * @return SegmentModel
     */
    public function withId(int $id): self
    {
        return $this->with('id', $id);
    }
}
