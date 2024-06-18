<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Route;

use Yuga\Route\Route;
use Yuga\Providers\ServiceProvider;
use Yuga\Interfaces\Application\Application;

class RouteServiceProvider extends ServiceProvider
{
    public function load(Application $app)
    {
        if (!$app->runningInConsole()) {
            $app->singleton('router', Route::class);
            $app->resolve('router')->start();
        }
    }

    
}