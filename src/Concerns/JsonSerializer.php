<?php

namespace Flagsmith\Concerns;

trait JsonSerializer
{
    /**
     * Build the object from JSON.
     * @param string $jsonDict
     * @return self
     */
    public static function build($jsonDict): self
    {
        $newObject = new self();
        $newObject->setValues($jsonDict);
        return $newObject;
    }

    /**
     * Set values from keys.
     * @param mixed $values
     * @return void
     */
    protected function setValues($values)
    {
        if ($values !== null) {
            foreach ($values as $key => $value) {
                if (isset($this->keys[$key])) {
                    $className = $this->keys[$key];
                    if (method_exists($className, 'build')) {
                        $this->{ $key } = $className::build($value);
                    } else {
                        $this->{ $key } = new $className($value);
                    }
                } else {
                    $this->{ $key } = $value;
                }
            }
        }
    }
}
