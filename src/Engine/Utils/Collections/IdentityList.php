<?php

namespace Flagsmith\Engine\Utils\Collections;

class IdentityList extends \ArrayObject implements \JsonSerializable
{
    use CollectionTrait;
    private string $list_type = 'Flagsmith\Engine\Identities\IdentityModel';
    /**
     * store identity models by identifiers.
     * @param mixed $key
     * @param IdentityModel $value
     * @return void
     */
    public function offsetSet($offset, $value): void
    {
        parent::offsetSet($value->identifier, $value);
    }
}
