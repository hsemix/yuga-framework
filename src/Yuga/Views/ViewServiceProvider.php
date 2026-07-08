<?php

/**
 * @author Mahad Tech Solutions
 */

namespace Yuga\Views;

use Yuga\Http\Request;
use Yuga\Interfaces\Application\Application;
use Yuga\Providers\ServiceProvider;
use Yuga\Views\Compilers\Compiler;
use Yuga\Views\Support\FragmentManager;
use Yuga\Views\Support\SectionManager;
use Yuga\Views\Support\ViewCache;
use Yuga\Views\Support\ViewComposerManager;

class ViewServiceProvider extends ServiceProvider
{
    public function load(Application $app)
    {
        $app->singleton('view', function () {
            $finder = new Finder(path('resources/views'));
            $cache = new ViewCache(path('storage/hax'));
            $sections = new SectionManager();
            $compiler = new Compiler($cache);
            $fragments = new FragmentManager();
            $composers = new ViewComposerManager();
            $engine = new Engine($finder, $compiler, $sections, $fragments, $composers);

            return new Factory($engine, $finder);
        });
    }
}
