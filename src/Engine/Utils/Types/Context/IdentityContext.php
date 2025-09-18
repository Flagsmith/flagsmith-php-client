<?php
namespace Flagsmith\Engine\Utils\Types\Context;

// TODO: Port this to https://wiki.php.net/rfc/dataclass
class IdentityContext
{
    /** @var string */
    public $key;

    /** @var string */
    public $identifier;

    /** @var array<string, mixed> */
    public $traits;
}
