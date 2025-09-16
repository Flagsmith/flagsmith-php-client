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

    /**
     * @param string $feature_key
     * @param string $name
     * @param bool $enabled
     * @param ?mixed $value
     * @param ?string $reason
     */
    public function __construct($feature_key, $name, $enabled, $value, $reason)
    {
        $this->feature_key = $feature_key;
        $this->name = $name;
        $this->enabled = $enabled;
        $this->value = $value;
        $this->reason = $reason;
    }
}
