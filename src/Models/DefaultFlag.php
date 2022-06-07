<?php

namespace Flagsmith\Models;

use Flagsmith\Concerns\HasWith;

class DefaultFlag extends BaseFlag
{
    public bool $is_default = true;
}
