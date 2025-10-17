<?php

namespace Flagsmith\Engine\Utils\Types\Result;

class EvaluationResult
{
    /** @var array<FlagResult> */
    public array $flags;

    /** @var array<SegmentResult> */
    public array $segments;
}
