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
}
