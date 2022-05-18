<?php

namespace Flagsmith\Engine\Features;

use Flagsmith\Concerns\HasWith;

class FlagsmithValue
{
    use HasWith;

    private string $value;
    private string $valueType = 'string';

    /**
     * Get the value.
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Build with value.
     * @param string $value
     * @return FlagsmithValue
     */
    public function withValue(string $value): self
    {
        return $this->with('value', $value);
    }

    /**
     * Get the Value Type.
     * @return string
     */
    public function getValueType(): string
    {
        return $this->valueType;
    }

    /**
     * Get the value type.
     * @param string $valueType
     * @return FlagsmithValue
     */
    public function withValueType(string $valueType): self
    {
        return $this->with('valueType', $valueType);
    }

    /**
     * Get the instance of Flagsmith Value.
     * @param mixed $untypedValue
     * @return FlagsmithValue
     */
    public static function fromUntypedValue($untypedValue): self
    {
        $type = gettype($untypedValue);

        return (new self())
            ->withValueType($type)
            ->withValue((string) $untypedValue);
    }

    /**
     * Build the object from JSON.
     * @param string $content
     * @return self
     */
    public static function build($value): self
    {
        return self::fromUntypedValue($value);
    }
}
