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

    /**
     * @param string $key
     * @param string $name
     * @param array<SegmentRule> $rules
     * @param ?array<FeatureContext> $overrides
     */
    public function __construct($key, $name, $rules, $overrides)
    {
        $this->key = $key;
        $this->name = $name;
        $this->rules = $rules;
        $this->overrides = $overrides ?? [];
    }
}
