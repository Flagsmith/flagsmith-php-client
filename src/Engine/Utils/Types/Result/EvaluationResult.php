<?php

namespace Flagsmith\Engine\Utils\Types\Result;

// TODO: Port this to https://wiki.php.net/rfc/dataclass
class EvaluationResult
{
    /** @var array<FlagResult> */
    public array $flags;

    /** @var array<SegmentResult> */
    public array $segments;
}
