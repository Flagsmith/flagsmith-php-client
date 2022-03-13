<?php

namespace Flagsmith\Engine\Utils;

class Hashing
{
    public function getHashedPercentageForObjectIds(array $objectIds, int $iterations = 1)
    {
        $toHash = str_repeat(implode(',', $objectIds), $iterations);
        $toHashValue = md5($toHash);

        $toHashValueInt = gmp_init($toHashValue, 16);
        $value = floatval(bcdiv(bcmod(gmp_strval($toHashValueInt), 9999), 9998, 5)) * 100;

        if ($value == 100) {
            return self::getHashedPercentageForObjectIds($objectIds, $iterations + 1);
        }

        return $value;
    }
}
