<?php
namespace Yuga\Route\Support;

use Yuga\Http\Request;
use Yuga\Route\Router;

interface IRouterBootManager
{
    /**
     * Called when router loads it's routes
     *
     * @param Route $router
     * @param Request $request
     */
    public function boot(Request $request);
}