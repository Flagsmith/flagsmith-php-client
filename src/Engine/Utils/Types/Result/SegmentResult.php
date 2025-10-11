<?php

namespace Flagsmith\Engine\Utils\Types\Result;

class SegmentResult implements \JsonSerializable
{
    /** @var string */
    public $key;

    /** @var string */
    public $name;

    /** @var ?array<string,mixed> */
    public $metadata;

    public function jsonSerialize(): array
    {
        $data = [
            'key' => $this->key,
            'name' => $this->name,
        ];

        // 'metadata' is only added if there is any
        if (!empty($this->metadata)) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }
}
