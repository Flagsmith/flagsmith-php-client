<?php

namespace Flagsmith\Engine\Utils\Collections;

class SegmentRuleModelList extends \ArrayObject implements \JsonSerializable
{
    use CollectionTrait;
    private string $list_type = 'Flagsmith\Engine\Segments\SegmentRuleModel';
}
