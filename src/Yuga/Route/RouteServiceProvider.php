<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Route;

use Yuga\Application\Router;
use Yuga\Providers\ServiceProvider;
use Yuga\Interfaces\Application\Application;

class RouteServiceProvider extends ServiceProvider
{
    public function load(Application $app)
    {
        if (!$app->runningInConsole()) {
            $app->singleton('router', Router::class);
            $app->resolve('router');
        }
    }
}