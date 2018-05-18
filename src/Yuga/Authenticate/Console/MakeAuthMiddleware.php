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
        $availableMiddleware = new MiddleWare;
        $middleware = $availableMiddleware->routerMiddleWare;
        $middleware[str_ireplace('middleware', '', 'guest')] = env('APP_NAMESPACE', 'App').'\\Middleware\\RedirectIfAuthenticated';

        $generatedMiddleware = '[';
        foreach ($middleware as $alias => $ware) {
            $generatedMiddleware .= "\n\t\t'{$alias}' => \\". $ware. "::class,";
        }
        $generatedMiddleware .= "\n\t]";

        $middlewareClass = str_replace(
            '{middleware}',
            $generatedMiddleware.';',
            file_get_contents(__DIR__.'/../../Http/Console/temps/YugaMiddleWare.temp')
        );

        file_put_contents(__DIR__.'/../../Http/Middleware/MiddleWare.php', $middlewareClass);
    }
}