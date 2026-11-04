<?php

namespace Flagsmith\Engine\Utils\Types\Context;

class FeatureValue
{
    /** @var ?string */
    public $key;

    /** @var mixed */
    public $value;

    /** @var float */
    public $weight;

    /** @var int */
    public $priority;
}
