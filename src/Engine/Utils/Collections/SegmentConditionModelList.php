<?php

namespace Flagsmith\Engine\Utils\Collections;

class SegmentConditionModelList extends \ArrayObject implements \JsonSerializable
{
    use CollectionTrait;
    private string $list_type = 'Flagsmith\Engine\Segments\SegmentConditionModel';
}
