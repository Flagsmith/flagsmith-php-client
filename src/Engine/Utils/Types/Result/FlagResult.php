<?php
namespace Flagsmith\Engine\Utils\Types\Result;

// TODO: Port this to https://wiki.php.net/rfc/dataclass
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
