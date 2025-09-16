<?php
namespace Flagsmith\Engine\Utils\Types\Context;

enum RuleType: string
{
    case ALL = 'ALL';
    case ANY = 'ANY';
    case NONE = 'NONE';
}
