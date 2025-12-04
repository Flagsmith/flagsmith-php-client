<?php

namespace Flagsmith\Engine\Segments;

use Flagsmith\Concerns\HasWith;
use Flagsmith\Concerns\JsonSerializer;

#[\AllowDynamicProperties]
class SegmentConditionModel
{
    use HasWith;
    use JsonSerializer;

    public string $operator;
    public ?string $value;
    public ?string $property_;
    private array $keys = [];

    /**
     * get property.
     * @return string
     */
    public function getProperty(): ?string
    {
        return $this->property_;
    }

    /**
     * build with property.
     * @param string $property_
     * @return SegmentConditionModel
     */
    public function withProperty(?string $property_): self
    {
        return $this->with('property_', $property_);
    }

    /**
     * get the value.
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * build with value.
     * @param string $value
     * @return SegmentConditionModel
     */
    public function withValue(?string $value): self
    {
        return $this->with('value', $value);
    }

    /**
     * get the operator.
     * @return string
     */
    public function getOperator(): string
    {
        return $this->operator;
    }

    /**
     * build with operator.
     * @param string $operator
     * @return SegmentConditionModel
     */
    public function withOperator(string $operator): self
    {
        return $this->with('operator', $operator);
    }
}
