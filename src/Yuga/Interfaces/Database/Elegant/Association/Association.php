<?php

declare(strict_types=1);

namespace Yuga\Interfaces\Database\Elegant\Association;

use Closure;

interface Association
{
    public function noConditions(Closure $callback);
}
