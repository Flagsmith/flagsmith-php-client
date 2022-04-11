<?php

namespace Flagsmith\Utils\Collections;

use ArrayObject;
use Flagsmith\Engine\Utils\Collections\CollectionTrait;

class FlagModelsList extends ArrayObject implements \JsonSerializable
{
    use CollectionTrait;
    private string $list_type = 'Flagsmith\Models\BaseFlag';

    public function __construct($list = [])
    {
        parent::__construct($list, ArrayObject::ARRAY_AS_PROPS);
    }
}
