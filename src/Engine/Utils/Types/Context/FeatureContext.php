<?php
namespace Flagsmith\Engine\Utils\Types\Context;

use JsonSerializable;

// TODO: Port this to https://wiki.php.net/rfc/dataclass
class FeatureContext implements JsonSerializable
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

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $json = [
            'key' => $this->key,
            'feature_key' => $this->feature_key,
            'name' => $this->name,
            'enabled' => $this->enabled,
            'value' => $this->value,
        ];

        if ($this->priority !== null) {
            $json['priority'] = $this->priority;
        }

        if ($this->variants) {
            $json['variants'] = $this->variants;
        }

        return $json;
    }
}
