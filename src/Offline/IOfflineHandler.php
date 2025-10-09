<?php

declare(strict_types=1);

namespace Flagsmith\Offline;

use Flagsmith\Engine\Utils\Types\Context\EvaluationContext;

interface IOfflineHandler
{
    public function getEvaluationContext(): EvaluationContext;
}
