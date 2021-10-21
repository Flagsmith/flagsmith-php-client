<?php

namespace Flagsmith\Concerns;

trait HasWith
{
    /**
     * Clones class with new property
     *
     * @param string $property
     * @param mixed $value
     * @return self
     */
    protected function with(string $property, $value): self
    {
        $self = clone $this;
        $self->{$property} = $value;
        return $self;
    }
}
