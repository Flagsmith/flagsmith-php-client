<?php

namespace Flagsmith\Engine\Utils\Types\Result;

use Flagsmith\Engine\Utils\Types\Context\EvaluationContext;

// TODO: Port this to https://wiki.php.net/rfc/dataclass
class EvaluationResult
{
    /** @var EvaluationContext */
    public $context;

    /** @var array<FlagResult> */
    public array $flags;

    /** @var array<SegmentResult> */
    public array $segments;
}
