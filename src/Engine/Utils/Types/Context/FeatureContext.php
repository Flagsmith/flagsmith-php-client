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

    /** @var ?mixed */
    public $value;

    /** @var ?float */
    public $priority;

    /** @var array<FeatureValue> */
    public $variants;
}
