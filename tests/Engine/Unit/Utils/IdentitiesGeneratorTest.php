<?php

declare(strict_types=1);

namespace Engine\Unit\Utils;

use Flagsmith\Utils\IdentitiesGenerator;
use PHPUnit\Framework\TestCase;

class IdentitiesGeneratorTest extends TestCase
{
    public function testGenerateIdentitiesCacheKey(): void
    {
        $identityId = 'test-identity-id';
        $traits = (object) ['key' => 'value'];
        $cacheKey = IdentitiesGenerator::generateIdentitiesCacheKey($identityId, $traits, null);

        $this->assertStringContainsString('Identity.', $cacheKey);
        $this->assertStringContainsString(sha1($identityId), $cacheKey);
    }

    public function testGenerateIdentitiesTransientCacheKey(): void
    {
        $identityId = 'test-identity-id';
        $traits = (object) ['key' => 'value'];
        $cacheKey = IdentitiesGenerator::generateIdentitiesCacheKey($identityId, $traits, true);

        $this->assertStringContainsString('Identity.Transient', $cacheKey);
        $this->assertStringContainsString(sha1($identityId), $cacheKey);
    }
}
