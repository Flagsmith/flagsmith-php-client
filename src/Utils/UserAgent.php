<?php

namespace Flagsmith\Utils;

/**
 * UserAgent utility class for generating SDK user agent strings.
 */
class UserAgent
{
    private static ?string $version = null;

    /**
     * Get the user agent string for the SDK.
     *
     * @return string The user agent string in the format "flagsmith-php-sdk/{version}"
     */
    public static function get(): string
    {
        return 'flagsmith-php-sdk/' . self::getVersion();
    }

    /**
     * Get the SDK version.
     *
     * @return string The version from composer.json, or "unknown"
     */
    public static function getVersion(): string
    {
        // The version cannot change within a process, so composer.json is read at most once.
        return self::$version ??= self::readVersion();
    }

    private static function readVersion(): string
    {
        try {
            $composerPath = __DIR__ . '/../../composer.json';
            $content = file_get_contents($composerPath);
            $data = $content === false ? null : json_decode($content, true);
            $version = $data['version'] ?? null;

            if ($version) {
                return $version;
            }
        } catch (\Exception $e) {
            // Silently fall through to default
        }

        return 'unknown';
    }
}
