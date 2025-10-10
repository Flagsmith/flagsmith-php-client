<?php

namespace Flagsmith\Engine\Utils;

class StringValue
{
    /**
     * @param mixed $value
     * @return string
     */
    public static function from($value): string
    {
        if ($value === null) {
            return 'null';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        return (string) $value;
    }
}
