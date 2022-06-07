<?php

namespace Flagsmith\Engine\Features;

use Flagsmith\Concerns\HasWith;
use Flagsmith\Concerns\JsonSerializer;

class FeatureSegmentModel
{
    use HasWith;
    use JsonSerializer;

    public int $priority;

    /**
     * Get priority.
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Build with priority.
     * @param int $priority
     * @return FeatureSegmentModel
     */
    public function withPriority(int $priority): self
    {
        return $this->with('priority', $priority);
    }
}
