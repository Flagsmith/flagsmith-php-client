<?php

namespace Flagsmith\Engine\Features;

use Flagsmith\Concerns\HasWith;
use Flagsmith\Concerns\JsonSerializer;

class MultivariateFeatureOptionModel
{
    use HasWith;
    use JsonSerializer;

    public int $id;
    public FlagsmithValue $value;

    private array $keys = [
        'value' => 'Flagsmith\Engine\Features\FlagsmithValue',
    ];

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
     * Get the value.
     * @return FlagsmithValue
     */
    public function getValue(): FlagsmithValue
    {
        return $this->value;
    }

    /**
     * build with value.
     * @param $value
     * @return MultivariateFeatureOptionModel
     */
    public function withValue(FlagsmithValue $value): self
    {
        return $this->with('value', $value);
    }
}
