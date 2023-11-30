<?php

namespace Yuga\Models\Console;

use Yuga\Support\Inflect;
use Yuga\Database\Elegant\Model;

trait CreateRoutes
{
    /**
     * Make the scaffold for a routes
     * 
     * @param \Yuga\Database\Elegant\Model $model
     * @param mixed
     */
    protected function processRoutes(Model $model)
    {
        $name = \class_base($model);
        $property = \strtolower($name);
        
        $routes = str_replace(
            ['{routes}', '{class}', '{classes}'], 
            [Inflect::pluralize($property), $name, Inflect::pluralize($name)], 
            file_get_contents(__DIR__.'/temps/scaffold/routes.temp')
        );
        file_put_contents(
            path('routes/web.php'),
            $routes,
            FILE_APPEND
        );
    }
}