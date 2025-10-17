<?php

namespace Flagsmith\Engine\Utils\Types\Context;

enum SegmentConditionOperator: string
{
    case CONTAINS = 'CONTAINS';
    case EQUAL = 'EQUAL';
    case GREATER_THAN = 'GREATER_THAN';
    case GREATER_THAN_INCLUSIVE = 'GREATER_THAN_INCLUSIVE';
    case IN = 'IN';
    case IS_NOT_SET = 'IS_NOT_SET';
    case IS_SET = 'IS_SET';
    case LESS_THAN = 'LESS_THAN';
    case LESS_THAN_INCLUSIVE = 'LESS_THAN_INCLUSIVE';
    case MODULO = 'MODULO';
    case NOT_CONTAINS = 'NOT_CONTAINS';
    case NOT_EQUAL = 'NOT_EQUAL';
    case PERCENTAGE_SPLIT = 'PERCENTAGE_SPLIT';
    case REGEX = 'REGEX';
}
