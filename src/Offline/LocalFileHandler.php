<?php

declare(strict_types=1);

namespace Flagsmith\Offline;

use Flagsmith\Engine\Environments\EnvironmentModel;
use Flagsmith\Exceptions\FlagsmithClientError;

class LocalFileHandler implements IOfflineHandler {
    private ?EnvironmentModel $environmentModel = null;

    public function __construct(string $filePath) {
        if ( !file_exists($filePath) ) {
            throw new FlagsmithClientError("Unable to read environment from file ".$filePath);
        }

        $file = fopen($filePath, "r");
        $environmentDict = json_decode(fread($file, filesize($filePath)), false, 512, JSON_THROW_ON_ERROR);
        $this->environmentModel = EnvironmentModel::build($environmentDict);
    }

    public function getEnvironment(): ?EnvironmentModel
    {
        return $this->environmentModel;
    }
}
