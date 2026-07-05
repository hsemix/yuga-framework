<?php

/**
 * @author Mahad Tech Solutions
 */

namespace Yuga\Views;

use Yuga\Http\Request;
use Yuga\Interfaces\Application\Application;
use Yuga\Providers\ServiceProvider;
use Yuga\Views\Support\SectionManager;
use Yuga\Views\Support\ViewCache;

class ViewServiceProvider extends ServiceProvider
{
    public function load(Application $app)
    {
        $app->singleton('view', function () {
            $finder = new Finder(path('resources/views'));
            $cache = new ViewCache(path('storage/hax'));
            $sections = new SectionManager();
            $compiler = new Compiler($cache);
            $engine = new Engine($finder, $compiler, $sections);

            return new Factory($engine, $finder);
        });
    }
}
