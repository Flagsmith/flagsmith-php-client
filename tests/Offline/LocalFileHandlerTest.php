<?php

use Flagsmith\Engine\Utils\Types\Context\EvaluationContext;
use Flagsmith\Offline\LocalFileHandler;
use PHPUnit\Framework\TestCase;

class LocalFileHandlerTest extends TestCase
{
    public function testLocalFileHandler()
    {
        // Given
        $filePath = __DIR__ . '/../Data/environment.json';

        // When
        $fileHandler = new LocalFileHandler($filePath);

        // Then
        $context = $fileHandler->getEvaluationContext();
        $this->assertInstanceOf(EvaluationContext::class, $context);
        $this->assertEquals('Test environment', $context->environment->name);
    }
}
