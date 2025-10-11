<?php

namespace Flagsmith\Engine\Utils\Types\Context;

class SegmentCondition
{
    /** @var string */
    public $property;

    /** @var SegmentConditionOperator */
    public $operator;

    /** @var string|array<string>  */
    public $value;
}
