<?php

use PHPUnit\Framework\TestCase;
use Flagsmith\Utils\UserAgent;

class UserAgentTest extends TestCase
{
    public function testGetUserAgentReturnsVersionFromComposerJson()
    {
        $userAgent = UserAgent::get();

        $composerPath = __DIR__ . '/../composer.json';
        $composerData = json_decode(file_get_contents($composerPath), true);
        $expectedVersion = $composerData['version'] ?? 'unknown';

        $this->assertEquals("flagsmith-php-sdk/{$expectedVersion}", $userAgent);
    }
}
