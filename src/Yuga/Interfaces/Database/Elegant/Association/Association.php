<?php

namespace Yuga\Interfaces\Database\Elegant\Association;

use Closure;

interface Association
{
    public function noConditions(Closure $callback);
}
