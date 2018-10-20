<?php
namespace Yuga\Route\Rewrite;

use Yuga\Http\Request;
use Yuga\Application\Router;
use Yuga\Providers\ServiceProvider;
use Yuga\Route\Support\IRouterBootManager;
use Yuga\Interfaces\Application\Application;

class RouteRewriteServiceProvider extends ServiceProvider implements IRouterBootManager
{

    public function load(Application $app)
    {
        if (!$app->runningInConsole()) {
            Router::router()->setBootManagers([$this]);
        }
    }

    public function boot(Request $request) {

        $rewriteRules = require path('config/RouteRewriteRules.php');

        foreach($rewriteRules as $url => $rule) {

            if($request->getUri() === $url) {
                $request->setRewriteUrl($rule);
                return $request;
            }
        }
    }
}