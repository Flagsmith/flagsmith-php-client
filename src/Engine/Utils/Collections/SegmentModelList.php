<?php

namespace Flagsmith\Engine\Utils\Collections;

class SegmentModelList extends \ArrayObject implements \JsonSerializable
{
    use CollectionTrait;
    private string $list_type = 'Flagsmith\Engine\Segments\SegmentModel';
}
