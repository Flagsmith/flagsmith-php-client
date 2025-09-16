<?php
namespace Flagsmith\Engine\Utils\Types\Result;

// TODO: Port this to https://wiki.php.net/rfc/dataclass
class SegmentResult
{
    /** @var string */
    public $key;

    /** @var string */
    public $name;

    /**
     * @param string $key
     * @param string $name
     */
    public function __construct($key, $name)
    {
        $this->key = $key;
        $this->name = $name;
    }
}
