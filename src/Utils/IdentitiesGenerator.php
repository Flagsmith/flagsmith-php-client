<?php

namespace Flagsmith\Utils;

class IdentitiesGenerator
{
    public static function generateIdentitiesData(string $identifier, ?object $traits, ?bool $transient)
    {
        $identityData = [
            'identifier' => $identifier,
            'traits' => [],
        ];

        if ($transient) {
            $identityData['transient'] = true;
        }

        if (!empty($traits)) {
            foreach ($traits as $key => $value) {
                $traitData = ['trait_key' => $key];
                if (is_object($value)) {
                    $traitData['trait_value'] = $value->value;
                    if ($value->transient) {
                        $traitData['transient'] = true;
                    }
                } else {
                    $traitData['trait_value'] = $value;
                }
                $identityData['traits'][] = $traitData;
            }
        }

        return $identityData;
    }

    public static function generateIdentitiesCacheKey(string $identifier, ?object $traits, ?bool $transient)
    {
        $hashedTraits = $traits !== null ? '.'.sha1(serialize($traits)) : '';
        $hashedIdentifier = sha1($identifier);
        return 'Identity.'.$transient ? 'Transient' : ''.$hashedIdentifier.$hashedTraits;
    }
}
