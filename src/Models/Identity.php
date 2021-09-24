<?php

namespace Flagsmith\Models;

use Flagsmith\Concerns\HasWith;

class Identity
{
    use HasWith;

    private string $id;
    private ?array $traits = null;
    private ?array $flags = null;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    /**
     * Set a trait for the identity.
     *
     * @param IdentityTrait $trait
     * @return self
     */
    public function withTrait(IdentityTrait $trait): self
    {
        $this->traits = $this->traits ?? [];
        return $this->with('traits', [...$this->traits, $trait]);
    }

    /**
     * Set all traits for the identity.
     *
     * @param array $traits
     * @return self
     */
    public function withTraits(array $traits): self
    {
        return $this->with('traits', $traits);
    }

    /**
     * Check if this Identity has traits
     *
     * @return boolean
     */
    public function hasTraits(): bool
    {
        return !is_null($this->traits);
    }

    /**
     * Get the value of traits
     *
     * @return array|null
     */
    public function getTraits(): ?array
    {
        return $this->traits;
    }

    /**
     * Get the value of id
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the value of flags
     *
     * @return array|null
     */
    public function getFlags(): ?array
    {
        return $this->flags;
    }

    /**
     * Set all Flags for the identity.
     *
     * @param array $flags
     * @return self
     */
    public function withFlags(array $flags): self
    {
        return $this->with('flags', $flags);
    }
}
