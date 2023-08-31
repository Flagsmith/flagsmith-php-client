<?php

namespace Flagsmith\Engine\Features;

use Flagsmith\Concerns\HasWith;
use Flagsmith\Concerns\JsonSerializer;

#[\AllowDynamicProperties]
class FeatureModel
{
    use HasWith;
    use JsonSerializer;

    public string $type;
    public string $name;
    public int $id;

    /**
     * Get ID.
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }
    /**
     * Build with ID.
     * @param int $id
     * @return FeatureModel
     */
    public function withId(int $id): self
    {
        return $this->with('id', $id);
    }

    /**
     * Get Name.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Build with Name.
     * @param string $name
     * @return FeatureModel
     */
    public function withName(string $name): self
    {
        return $this->with('name', $name);
    }

    /**
     * get Type.
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * build with Type.
     * @param string $type
     * @return FeatureModel
     */
    public function withType(string $type): self
    {
        return $this->with('type', $type);
    }
}
