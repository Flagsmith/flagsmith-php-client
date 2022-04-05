<?php

namespace Flagsmith\Engine\Segments;

use Flagsmith\Concerns\HasWith;
use Flagsmith\Concerns\JsonSerializer;
use Flagsmith\Engine\Segments\SegmentConditions;

class SegmentConditionModel
{
    use HasWith;
    use JsonSerializer;

    public string $operator;
    public string $value;
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
    public function withValue(string $value): self
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

    public const EQUALS = 'EQUALS';

    public function matchesTraitValue($traitValue): bool
    {
        $condition = false;
        $castedValue = $this->value;

        if ('boolean' === gettype($traitValue)) {
            $castedValue = filter_var($castedValue, FILTER_VALIDATE_BOOLEAN);
        } else {
            settype($castedValue, gettype($traitValue));
        }

        switch ($this->operator) {
            case (SegmentConditions::EQUAL):
                $condition = $traitValue == $castedValue;
                break;
            case (SegmentConditions::GREATER_THAN):
                $condition = $traitValue > $castedValue;
                break;
            case (SegmentConditions::GREATER_THAN_INCLUSIVE):
                $condition = $traitValue >= $castedValue;
                break;
            case (SegmentConditions::LESS_THAN):
                $condition = $traitValue < $castedValue;
                break;
            case (SegmentConditions::LESS_THAN_INCLUSIVE):
                $condition = $traitValue <= $castedValue;
                break;
            case (SegmentConditions::NOT_EQUAL):
                $condition = $traitValue !=$castedValue;
                break;
            case (SegmentConditions::CONTAINS):
                $condition = strpos($traitValue, (string) $castedValue) !== false;
                break;
            case (SegmentConditions::NOT_CONTAINS):
                $condition = strpos($traitValue, (string) $castedValue) === false;
                break;
            case (SegmentConditions::REGEX):
                $matchesCount = preg_match_all("/{$castedValue}/", (string) $traitValue);
                $condition = $matchesCount && $matchesCount > 0;
                break;
        }

        return $condition;
    }
}
