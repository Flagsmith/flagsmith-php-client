<?php

namespace Flagsmith\Models;

use Flagsmith\Concerns\HasWith;
use Flagsmith\Engine\Features\FlagsmithValue;
use Flagsmith\Engine\Features\FlagsmithValueType;

class BaseFlag
{
    use HasWith;
    public bool $enabled;
    public FlagsmithValue $value;
    public bool $is_default = false;

    /**
     * Get the is default bool.
     * @return bool
     */
    public function getIsDefault(): bool
    {
        return $this->is_default;
    }

    /**
     * Build with is default.
     * @param bool $isDefault
     * @return BaseFlag
     */
    public function withIsDefault(bool $isDefault): self
    {
        return $this->with('is_default', $isDefault);
    }

    /**
     * Get the value.
     * @return FlagsmithValue
     */
    public function getValue(): FlagsmithValue
    {
        return $this->value;
    }

    /**
     * Build with value.
     * @param mixed $value
     * @return BaseFlag
     */
    public function withValue($value): self
    {
        return $this->with('value', FlagsmithValue::fromUntypedValue($value));
    }

    /**
     * Get the enabled boolean.
     * @return bool
     */
    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Build with enabled boolean.
     * @param bool $enabled
     * @return BaseFlag
     */
    public function withEnabled(bool $enabled): self
    {
        return $this->with('enabled', $enabled);
    }

    /**
     * Get the String value.
     * @return string|null
     */
    public function getStringValue(): ?string
    {
        if ($this->value->getValueType === FlagsmithValueType::STRING) {
            return $this->value->getValue();
        }

        return null;
    }

    /**
     * Get the Boolean value.
     * @return bool|null
     */
    public function getBooleanValue(): ?bool
    {
        if ($this->value->getValueType === FlagsmithValueType::BOOLEAN) {
            return strtolower($this->value->getValue()) === 'true';
        }

        return null;
    }

    /**
     * Get the String value.
     * @return int|null
     */
    public function getIntegerValue(): ?int
    {
        if ($this->value->getValueType === FlagsmithValueType::FLOAT) {
            return intval($this->value->getValue());
        }

        return null;
    }

    /**
     * Get the String value.
     * @return float|null
     */
    public function getFloatValue(): ?float
    {
        if ($this->value->getValueType === FlagsmithValueType::FLOAT) {
            return floatval($this->value->getValue());
        }

        return null;
    }
}
