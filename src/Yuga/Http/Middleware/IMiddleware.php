<?php

declare(strict_types=1);

namespace Yuga\Http\Middleware;

use Closure;
use Yuga\Http\Request;

interface IMiddleware
{
    /**
     * Route Middleware
     * @return Request|null
     */
    public function run(Request $request, Closure $next);

}
