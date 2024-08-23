<?php

declare(strict_types=1);

namespace Flagsmith\Offline;

use Flagsmith\Engine\Environments\EnvironmentModel;

interface IOfflineHandler {
    public function getEnvironment(): ?EnvironmentModel;
}
