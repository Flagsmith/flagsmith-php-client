<?php

namespace Flagsmith\Models;

use Flagsmith\Concerns\HasWith;

class BaseFlag
{
    use HasWith;
    public bool $enabled;
    public $value;
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
     * @return mixed
     */
    public function getValue()
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
        return $this->with('value', $value);
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
}
