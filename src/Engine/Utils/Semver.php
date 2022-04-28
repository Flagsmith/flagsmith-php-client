<?php

namespace Flagsmith\Engine\Utils;

class Semver
{
    /**
     * Checks if the given string have `:semver` suffix or not
     *  >>> is_semver("2.1.41-beta:semver")
     *  True
     *  >>> is_semver("2.1.41-beta")
     *  False
     * @param string $semvar
     * @return bool
     */
    public static function isSemver(string $semver): bool
    {
        return substr($semver, -7) === ':semver';
    }

    /**
     * Remove the semver suffix(i.e: last 7 characters) from the given value
     *  >>> remove_semver_suffix("2.1.41-beta:semver")
     *  '2.1.41-beta'
     *  >>> remove_semver_suffix("2.1.41:semver")
     *  '2.1.41'
     * @param string $semver
     * @return string
     */
    public static function removeSemverSuffix(string $semver): string
    {
        return substr($semver, 0, -7);
    }
}
