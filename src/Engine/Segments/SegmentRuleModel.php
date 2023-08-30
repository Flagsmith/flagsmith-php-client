<?php

namespace Flagsmith\Engine\Segments;

use Flagsmith\Concerns\HasWith;
use Flagsmith\Concerns\JsonSerializer;
use Flagsmith\Engine\Utils\Collections\SegmentConditionModelList;
use Flagsmith\Engine\Utils\Collections\SegmentRuleModelList;

#[\AllowDynamicProperties]
class SegmentRuleModel
{
    use HasWith;
    use JsonSerializer;

    public string $type;
    public SegmentRuleModelList $rules;
    public SegmentConditionModelList $conditions;
    private array $keys = [
        'rules' => 'Flagsmith\Engine\Utils\Collections\SegmentRuleModelList',
        'conditions' => 'Flagsmith\Engine\Utils\Collections\SegmentConditionModelList',
    ];

    public function __construct()
    {
        $this->rules = new SegmentRuleModelList();
        $this->conditions = new SegmentConditionModelList();
    }

    /**
     * Get the conditions array.
     * @return SegmentConditionModelList
     */
    public function getConditions(): SegmentConditionModelList
    {
        return $this->conditions;
    }

    /**
     * build with conditions array.
     * @param SegmentConditionModelList $conditions
     * @return SegmentRuleModel
     */
    public function withConditions(SegmentConditionModelList $conditions): self
    {
        return $this->with('conditions', $conditions);
    }

    /**
     * get the rules array.
     * @return SegmentRuleModelList
     */
    public function getRules(): SegmentRuleModelList
    {
        return $this->rules;
    }

    /**
     * Build with the rules array.
     * @param SegmentRuleModelList $rules
     * @return SegmentRuleModelList
     */
    public function withRules(SegmentRuleModelList $rules): self
    {
        return $this->with('rules', $rules);
    }

    /**
     * get the type.
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * build with type.
     * @param string $type
     * @return SegmentRuleModel
     */
    public function withType(string $type): self
    {
        return $this->with('type', $type);
    }

    /**
     * Evaluate the bools.
     * @return \Closure
     */
    public function matchingFunction()
    {
        $type = $this->type;
        return function (array $list) use ($type) {
            switch ($type) {
                case SegmentRules::ALL_RULE:
                    $evaluation = SegmentEvaluator::all($list);
                    break;
                case SegmentRules::ANY_RULE:
                    $evaluation = SegmentEvaluator::any($list);
                    break;
                case SegmentRules::NONE_RULE:
                    $evaluation = SegmentEvaluator::none($list);
                    break;
            }

            return $evaluation;
        };
    }
}
