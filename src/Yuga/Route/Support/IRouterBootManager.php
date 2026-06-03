<?php

declare(strict_types=1);

namespace Yuga\Route\Support;

use Yuga\Http\Request;
use Yuga\Route\Router;

interface IRouterBootManager
{
    /**
     * Called when router loads it's routes
     *
     * @param Route $router
     */
    public function boot(Request $request);
}
