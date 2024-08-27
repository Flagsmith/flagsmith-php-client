<?php

declare(strict_types=1);

namespace FlagsmithTest\Offline;

use Flagsmith\Engine\Environments\EnvironmentModel;
use Flagsmith\Offline\IOfflineHandler;
use FlagsmithTest\ClientFixtures;

class FakeOfflineHandler implements IOfflineHandler
{
    public function getEnvironment(): ?EnvironmentModel
    {
        print 'Getting environment from FakeOfflineHandler';
        return ClientFixtures::getEnvironmentModel();
    }
}
