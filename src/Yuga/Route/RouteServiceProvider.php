<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Route;

use Yuga\Application\Router as Routing;
use Yuga\Providers\ServiceProvider;
use Yuga\Interfaces\Application\Application;

class RouteServiceProvider extends ServiceProvider
{
    public function load(Application $app)
    {
        if (!$app->runningInConsole()) {
            $app->singleton('router', Routing::class);
            $app->resolve('router');
        }
    }
}