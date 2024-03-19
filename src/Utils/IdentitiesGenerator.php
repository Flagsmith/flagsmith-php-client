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

    public static function generateIdentitiesCacheKey(string $identifier, ?object $traits)
    {
        $hashedTraits = $traits !== null ? '.'.sha1(serialize($traits)) : '';
        $hashedIdentifier = sha1($identifier);
        return 'Identity.'.$hashedIdentifier.$hashedTraits;
    }
}
