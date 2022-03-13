<?php

namespace Flagsmith\Engine\Utils;

trait HashingTrait
{
    private static Hashing $hashingObj;

    /**
     * set the hash object.
     * @param Hashing $hash
     * @return void
     */
    public static function setHashObject(Hashing $hash)
    {
        self::$hashingObj = $hash;
    }

    /**
     * Get the hash Object.
     * @return Hashing
     */
    public static function getHashObject()
    {
        if (empty(self::$hashingObj)) {
            self::$hashingObj = new Hashing();
        }

        return self::$hashingObj;
    }

    /**
     * Get the hashed Percentage.
     * @param array $objectIds
     * @param int $iterations
     * @return int
     */
    public function getHashedPercentageForObjectIds(array $objectIds, int $iterations = 1)
    {
        return self::getHashObject()->getHashedPercentageForObjectIds($objectIds, $iterations);
    }
}
