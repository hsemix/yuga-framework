<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Session;

use Yuga\Application;
use Yuga\Providers\ServiceProvider;

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