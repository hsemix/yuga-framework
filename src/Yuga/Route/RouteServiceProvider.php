<?php
/**
 * @author Mahad Tech Solutions
 */

namespace Yuga\Route;

use Yuga\Interfaces\Application\Application;
use Yuga\Providers\ServiceProvider;

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
