<?php

namespace Yuga\Route\Rewrite;

use Yuga\Http\Request;
use Yuga\Interfaces\Application\Application;
use Yuga\Providers\ServiceProvider;
use Yuga\Route\Route;
use Yuga\Route\Support\IRouterBootManager;

class RouteRewriteServiceProvider extends ServiceProvider implements IRouterBootManager
{
    public function load(Application $app)
    {
        if (!$app->runningInConsole()) {
            Route::router()->setBootManagers([$this]);
        }
    }

    public function boot(Request $request)
    {
        $rewriteRules = require path('config/RouteRewriteRules.php');

        foreach ($rewriteRules as $url => $rule) {
            if ($request->getUri() === $url) {
                $request->setRewriteUrl($rule);

                return $request;
            }
        }
    }
}
