<?php

namespace Flagsmith\Engine\Utils\Types\Context;

// TODO: Port this to https://wiki.php.net/rfc/dataclass
class SegmentCondition
{
    /** @var string */
    public $property;

    /** @var SegmentConditionOperator */
    public $operator;

    /** @var string|array<string>  */
    public $value;
}
