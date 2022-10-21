<?php
/**
 * @author Mahad Tech Solutions
 */

namespace Yuga\Views;

use Yuga\Http\Request;
use Yuga\Interfaces\Application\Application;
use Yuga\Providers\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function load(Application $app)
    {
        $app->singleton('view', HaxCompiler::class);
        $template = $app->resolve('view', [
            path('resources/views/'),
        ]);
        $template->resource = 'resources/assets/';
        $template->host = (new Request())->getHost();
        event('on:hax-render', ['compiler' => $template]);

        return $template;
    }
}
