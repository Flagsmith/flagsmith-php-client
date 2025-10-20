<?php

declare(strict_types=1);

namespace FlagsmithTest\Offline;

use Flagsmith\Offline\LocalFileHandler;

class FakeOfflineHandler extends LocalFileHandler
{
    public function __construct()
    {
        $this->filePath = __DIR__ . '/../Data/environment.json';
    }
}
