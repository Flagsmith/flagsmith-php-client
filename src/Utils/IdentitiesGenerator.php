<?php

namespace Flagsmith\Utils;

class IdentitiesGenerator
{
    public static function generateIdentitiesData(string $identifier, ?object $traits)
    {
        $identities = [
            'identifier' => $identifier,
            'traits' => [],
        ];

        if (!empty($traits)) {
            foreach ($traits as $key => $value) {
                $identities['traits'][] = ['trait_key' => $key, 'trait_value' => $value];
            }
        }

        return $identities;
    }
}
