<?php

namespace Flagsmith\Models;

use Flagsmith\Concerns\HasWith;

class Segment
{
    use HasWith;

    private int $id;
    private string $name;

    /**
     * Get the ID.
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Build with ID.
     * @param int $id
     * @return Segment
     */
    public function withId(int $id): self
    {
        return $this->with('id', $id);
    }

    /**
     * Get the name of the segment.
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * build with Name.
     * @param string $name
     * @return Segment
     */
    public function withName(string $name): self
    {
        return $this->with('name', $name);
    }
}
