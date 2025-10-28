<?php

namespace Flagsmith\Engine\Utils\Types\Result;

class FlagResult implements \JsonSerializable
{
    /** @var string */
    public $name;

    /** @var bool */
    public $enabled;

    /** @var ?mixed */
    public $value;

    /** @var ?string */
    public $reason;

    /** @var ?array<string,mixed> */
    public $metadata;

    public function jsonSerialize(): array
    {
        $data = get_object_vars($this);

        // 'metadata' is only added if there is any
        if (empty($this->metadata)) {
            unset($data['metadata']);
        }

        return $data;
    }
}
