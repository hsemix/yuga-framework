<?php
namespace Yuga\Authenticate\Console;

use Yuga\Http\Middleware\MiddleWare;

trait MakeAuthMiddleware
{
    protected function createAuthMiddleware()
    {
        $this->createMiddlewareFolder();
        $this->createRedirectIfAuthenticated();
        $this->compileAuthYugaMiddlewareTemp();
    }

    protected function createMiddlewareFolder()
    {
        if (!is_dir($directory = path('app/Middleware'))) {
            mkdir($directory, 0755, true);
        }
    }

    protected function createRedirectIfAuthenticated()
    {
        file_put_contents(
            path('app/Middleware/RedirectIfAuthenticated.php'),
            $this->compileRedirectIfAuthenticatedTemp()
        );
    }

    protected function compileRedirectIfAuthenticatedTemp()
    {
        return str_replace(
            '{namespace}',
            env('APP_NAMESPACE', 'App'),
            file_get_contents(__DIR__.'/temps/make/RedirectIfAuthenticated.temp')
        );
    }

    /**
     * Compiles the MiddlewWare temp.
     *
     * @return string
     */
    protected function compileAuthYugaMiddlewareTemp()
    {
        $middleware = require path('config/AppMiddleware.php');
        $middleware[str_ireplace('middleware', '', 'guest')] = env('APP_NAMESPACE', 'App').'\\Middleware\\RedirectIfAuthenticated';

        $generatedMiddleware = '[';
        foreach ($middleware as $alias => $ware) {
            $generatedMiddleware .= "\n\t'{$alias}' => \\". $ware. "::class,";
        }
        $generatedMiddleware .= "\n]";

        $middlewareClass = str_replace(
            '{middleware}',
            $generatedMiddleware.';',
            file_get_contents(__DIR__.'/../../Http/Console/temps/AppMiddleWare.temp')
        );

        file_put_contents(path('config/AppMiddleWare.php'), $middlewareClass);
    }
}