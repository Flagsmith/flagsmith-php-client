<?php

namespace Flagsmith\Engine\Segments;

class SegmentConditions
{
    public const EQUAL = 'EQUAL';
    public const GREATER_THAN = 'GREATER_THAN';
    public const LESS_THAN = 'LESS_THAN';
    public const LESS_THAN_INCLUSIVE = 'LESS_THAN_INCLUSIVE';
    public const CONTAINS = 'CONTAINS';
    public const GREATER_THAN_INCLUSIVE = 'GREATER_THAN_INCLUSIVE';
    public const NOT_CONTAINS = 'NOT_CONTAINS';
    public const NOT_EQUAL = 'NOT_EQUAL';
    public const REGEX = 'REGEX';
    public const PERCENTAGE_SPLIT = 'PERCENTAGE_SPLIT';
    public const IS_SET = 'IS_SET';
    public const IS_NOT_SET = 'IS_NOT_SET';
    public const MODULO = 'MODULO';
}
