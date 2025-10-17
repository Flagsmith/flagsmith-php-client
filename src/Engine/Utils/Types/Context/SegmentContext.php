<?php

namespace Flagsmith\Engine\Utils\Types\Context;

class SegmentContext
{
    /** @var string */
    public $key;

    /** @var string */
    public $name;

    /** @var array<SegmentRule> */
    public $rules;

    /** @var array<FeatureContext> */
    public $overrides;

    /** @var ?array<string,mixed> */
    public $metadata;
}
