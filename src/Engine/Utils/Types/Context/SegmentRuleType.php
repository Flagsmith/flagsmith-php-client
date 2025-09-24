<?php

namespace Flagsmith\Engine\Utils\Types\Context;

enum SegmentRuleType: string
{
    case ALL = 'ALL';
    case ANY = 'ANY';
    case NONE = 'NONE';
}
