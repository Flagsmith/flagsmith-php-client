<?php
namespace Flagsmith\Engine\Utils\Types\Context;

// TODO: Port this to https://wiki.php.net/rfc/dataclass
class SegmentRule
{
    /** @var RuleType */
    public $type;

    /** @var array<SegmentCondition> */
    public $conditions;

    /** @var array<SegmentRule> */
    public $rules;

    /**
     * @param RuleType $type
     * @param ?array<SegmentCondition> $conditions
     * @param ?array<SegmentRule> $rules
     */
    public function __construct($type, $conditions, $rules)
    {
        $this->type = $type;
        $this->conditions = $conditions ?? [];
        $this->rules = $rules ?? [];
    }
}
