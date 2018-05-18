<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Views;

use Yuga\Http\Request;
use Yuga\Application;
use Yuga\Providers\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function load(Application $app)
    {
        if (!$app->runningInConsole()) {
            $app->singleton('view', SmxView::class);
            $template = $app->resolve('view', [
                './resources/views/'
            ]);
            $template->resource = ((!is_null(env('APP_FOLDER'))) ? env('APP_FOLDER') . DIRECTORY_SEPARATOR : '').'resources/assets/';
            $template->host = (new Request)->getHost();
            
            return $template;
        }
    }
}