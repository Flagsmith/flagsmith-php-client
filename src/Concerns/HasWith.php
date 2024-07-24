<?php

namespace Flagsmith\Concerns;

trait HasWith
{
    /**
     * Set the new property
     *
     * @param string $property
     * @param mixed $value
     * @return self
     */
    protected function with(string $property, $value): self
    {
        $this->{$property} = $value;
        return $this;
    }
}
