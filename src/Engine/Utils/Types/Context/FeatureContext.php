<?php
namespace Flagsmith\Engine\Utils\Types\Context;

// TODO: Port this to https://wiki.php.net/rfc/dataclass
class FeatureContext
{
    /** @var string */
    public $key;

    /** @var string */
    public $feature_key;

    /** @var string */
    public $name;

    /** @var bool */
    public $enabled;

    /** @var mixed */
    public $value;

    /** @var float */
    public $priority;

    /** @var array<FeatureValue> */
    public $variants;

    /**
     * @param string $key
     * @param string $feature_key
     * @param string $name
     * @param bool $enabled
     * @param mixed $value
     * @param ?array<FeatureValue> $variants
     * @param ?float $priority
     */
    public function __construct(
        $key,
        $feature_key,
        $name,
        $enabled,
        $value,
        $variants,
        $priority,
    ) {
        $this->key = $key;
        $this->feature_key = $feature_key;
        $this->name = $name;
        $this->enabled = $enabled;
        $this->value = $value;
        $this->priority = $priority ?? INF;
        $this->variants = $variants ?? [];
    }
}
