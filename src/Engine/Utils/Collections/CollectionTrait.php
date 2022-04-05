<?php

namespace Flagsmith\Engine\Utils\Collections;

use Flagsmith\Concerns\JsonSerializer;

trait CollectionTrait
{
    use JsonSerializer;

    protected function setValues($values)
    {
        if (is_array($values)) {
            foreach ($values as $value) {
                $className = $this->list_type ?? [];

                $this->append($className::build($value));
            }
        }
    }

    /**
     * Returns the object to JSON serialize.
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->getArrayCopy();
    }
}
