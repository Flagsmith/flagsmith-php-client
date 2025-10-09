<?php

declare(strict_types=1);

namespace Flagsmith\Offline;

use Flagsmith\Engine\Environments\EnvironmentModel;
use Flagsmith\Exceptions\FlagsmithClientError;

class LocalFileHandler implements IOfflineHandler
{
    protected string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function getEnvironment(): ?EnvironmentModel
    {
        if (!file_exists($this->filePath)) {
            throw new FlagsmithClientError("Unable to read evaluation context from file {$this->filePath}");
        }

        $environmentDocument = json_decode(
            json: file_get_contents($this->filePath),
            associative: false,
            flags: JSON_THROW_ON_ERROR,
        );

        return EnvironmentModel::build($environmentDocument);
    }
}
