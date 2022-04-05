<?php

namespace Flagsmith\Engine\Identities\Traits;

use Flagsmith\Concerns\HasWith;
use Flagsmith\Concerns\JsonSerializer;

class TraitModel
{
    use HasWith;
    use JsonSerializer;

    public string $trait_key;
    public $trait_value;

    /**
     * Get the trait value.
     * @return mixed
     */
    public function getTraitValue()
    {
        return $this->trait_value;
    }

    /**
     * build with trait value.
     * @param mixed $trait_value
     * @return TraitModel
     */
    public function withTraitValue($trait_value): self
    {
        return $this->with('trait_value', $trait_value);
    }

    /**
     * get the trait key.
     * @return string
     */
    public function getTraitKey(): string
    {
        return $this->trait_key;
    }

    /**
     * build with trait key.
     * @param string $trait_key
     * @return TraitModel
     */
    public function withTraitKey(string $trait_key): self
    {
        return $this->with('trait_key', $trait_key);
    }
}
