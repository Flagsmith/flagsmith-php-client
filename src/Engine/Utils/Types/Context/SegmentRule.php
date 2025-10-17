<?php

namespace Flagsmith\Engine\Utils\Types\Context;

class SegmentRule
{
    /** @var RuleType */
    public $type;

    /** @var array<SegmentCondition> */
    public $conditions;

    /** @var array<SegmentRule> */
    public $rules;
}
