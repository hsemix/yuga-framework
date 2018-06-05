<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Session;

use Yuga\Providers\ServiceProvider;
use Yuga\Interfaces\Application\Application;

class SessionServiceProvider extends ServiceProvider
{
    public function load(Application $app)
    {
        if (!$app->runningInConsole()) {
            $app->singleton('session', Session::class);
            $app->resolve('session');
        }
    }
}