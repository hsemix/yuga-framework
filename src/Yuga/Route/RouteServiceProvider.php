<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Route;
use Yuga\Router;
use Yuga\Application;
use Yuga\Providers\ServiceProvider;
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