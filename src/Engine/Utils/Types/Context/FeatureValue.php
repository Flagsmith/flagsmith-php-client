<?php
namespace Flagsmith\Engine\Utils\Types\Context;

// TODO: Port this to https://wiki.php.net/rfc/dataclass
class FeatureValue
{
    /** @var mixed */
    public $value;

    /** @var float */
    public $weight;

    /**
     * @param mixed $value
     * @param float $weight
     */
    public function __construct($value, $weight)
    {
        $this->value = $value;
        $this->weight = $weight;
    }
}
