<?php

use Flagsmith\Engine\Environments\EnvironmentAPIKeyModel;
use PHPUnit\Framework\TestCase;

use function PHPUnit\Framework\assertFalse;

class EnvironmentModelTest extends TestCase
{
    public function testEnvironmentApiKeyModelIsValidIsTrueForNonExpiredActiveKey()
    {
        $environmentApiKeyModel = new EnvironmentAPIKeyModel();
        $environmentApiKeyModel = $environmentApiKeyModel
            ->withId(1)
            ->withKey('ser.random_key')
            ->withCreatedAt(new DateTime('now'))
            ->withClientApiKey('clientKey');

        $this->assertTrue($environmentApiKeyModel->isValid());
    }

    public function testEnvironmentApiKeyModelIsValidIsTrueForNonExpiredActiveKeyWithExpiredDateInFuture()
    {
        $environmentApiKeyModel = new EnvironmentAPIKeyModel();
        $environmentApiKeyModel = $environmentApiKeyModel
            ->withId(1)
            ->withKey('ser.random_key')
            ->withCreatedAt(new DateTime('now'))
            ->withExpiresAt((new DateTime('now'))->modify('+ 5 day'))
            ->withClientApiKey('clientKey');

        $this->assertTrue($environmentApiKeyModel->isValid());
    }

    public function testEnvironmentApiKeyModelIsValidIsFalseForExpiredActiveKey()
    {
        $environmentApiKeyModel = new EnvironmentAPIKeyModel();
        $environmentApiKeyModel = $environmentApiKeyModel
            ->withId(1)
            ->withKey('ser.random_key')
            ->withCreatedAt((new DateTime('now'))->modify('- 5 day'))
            ->withExpiresAt((new DateTime('now')))
            ->withClientApiKey('clientKey');

        $this->assertFalse($environmentApiKeyModel->isValid());
    }

    public function testEnvironmentApiKeyModelIsValidIsFalseForNonExpiredInactiveKey()
    {
        $environmentApiKeyModel = new EnvironmentAPIKeyModel();
        $environmentApiKeyModel = $environmentApiKeyModel
            ->withId(1)
            ->withKey('ser.random_key')
            ->withCreatedAt(new DateTime('now'))
            ->withClientApiKey('clientKey')
            ->withActive(false);

        $this->assertFalse($environmentApiKeyModel->isValid());
    }
}
