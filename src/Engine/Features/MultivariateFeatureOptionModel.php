<?php

namespace Flagsmith\Engine\Features;

use Flagsmith\Concerns\HasWith;
use Flagsmith\Concerns\JsonSerializer;

#[\AllowDynamicProperties]
class MultivariateFeatureOptionModel
{
    use HasWith;
    use JsonSerializer;
    public int $id;
    public $value;

    /**
     * Get the ID.
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * build with ID.
     * @param int $id
     * @return MultivariateFeatureOptionModel
     */
    public function withId(int $id): self
    {
        return $this->with('id', $id);
    }

    /**
     * get the value.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * build with value.
     * @param $value
     * @return MultivariateFeatureOptionModel
     */
    public function withValue($value): self
    {
        return $this->with('value', $value);
    }
}
