<?php

namespace Flagsmith\Utils;

/**
 * UserAgent utility class for generating SDK user agent strings.
 */
class UserAgent
{
    /**
     * Get the user agent string for the SDK.
     *
     * @return string The user agent string in the format "flagsmith-php-sdk/{version}"
     */
    public static function get(): string
    {
        try {
            $composerPath = __DIR__ . '/../../composer.json';
            $content = file_get_contents($composerPath);
            $data = json_decode($content, true);
            $version = $data['version'] ?? null;

            if ($version) {
                return "flagsmith-php-sdk/{$version}";
            }
        } catch (\Exception $e) {
            // Silently fall through to default
        }

        return 'flagsmith-php-sdk/unknown';
    }
}
