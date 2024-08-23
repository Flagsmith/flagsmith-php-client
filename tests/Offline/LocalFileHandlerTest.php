<?php

use Flagsmith\Offline\LocalFileHandler;
use FlagsmithTest\ClientFixtures;
use PHPUnit\Framework\TestCase;

class LocalFileHandlerTest extends TestCase {
    public function testLocalFileHandler() {
        // Given
        $environmentModel = ClientFixtures::getEnvironmentModel();

        // When
        $localFileHandler = new LocalFileHandler(dirname(__FILE__)."/../Data/environment.json");

        // Then
        $this->assertEquals($localFileHandler->getEnvironment(), $environmentModel);
    }
}
