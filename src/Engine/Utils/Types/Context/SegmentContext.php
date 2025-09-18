<?php
namespace Flagsmith\Engine\Utils\Types\Context;

// TODO: Port this to https://wiki.php.net/rfc/dataclass
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
}
