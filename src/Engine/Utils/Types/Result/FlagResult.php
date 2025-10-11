<?php

namespace Flagsmith\Engine\Utils\Types\Result;

class FlagResult
{
    /** @var string */
    public $feature_key;

    /** @var string */
    public $name;

    /** @var bool */
    public $enabled;

    /** @var ?mixed */
    public $value;

    /** @var ?string */
    public $reason;
}
