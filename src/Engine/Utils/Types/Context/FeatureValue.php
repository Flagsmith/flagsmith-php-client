<?php

namespace Flagsmith\Engine\Utils\Types\Context;

// TODO: Port this to https://wiki.php.net/rfc/dataclass
class FeatureValue
{
    /** @var mixed */
    public $value;

    /** @var float */
    public $weight;
}
