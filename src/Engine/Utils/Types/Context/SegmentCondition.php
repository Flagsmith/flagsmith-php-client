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

    /**
     * @param string $property
     * @param SegmentConditionOperator $operator
     * @param string|array<string> $value
     */
    public function __construct($property, $operator, $value)
    {
        $this->property = $property;
        $this->operator = $operator;
        $this->value = $value;
    }
}
