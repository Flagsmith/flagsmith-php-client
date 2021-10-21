<?php

namespace Flagsmith\Models;

use Flagsmith\Concerns\HasWith;

class IdentityTrait
{
    use HasWith;

    private ?int $id = null;
    private string $key;
    private ?string $value = null;

    public function __construct(string $key, ?string $value = null)
    {
        $this->key = $key;
        $this->value = $value;
    }

    /**
     * Get the value of key
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Get the value of value
     *
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * Set the value of value
     *
     * @param string|null $value
     *
     * @return self
     */
    public function withValue(?string $value = null): self
    {
        return $this->with('value', $value);
    }

    /**
     * Get the value of id
     *
     * @return ?int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @param int|null $id
     *
     * @return self
     */
    public function withId(?int $id): self
    {
        return $this->with('id', $id);
    }
}
