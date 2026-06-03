<?php

namespace Yuga\Models\Console;

use Yuga\Support\Inflect;
use Yuga\Database\Elegant\Model;

trait CreateControllers
{
    /**
     * Make the scaffold for a controller
     *
     * @param mixed
     */
    protected function processControllers(Model $model)
    {
        $name = \class_base($model);
        $property = \strtolower((string) $name);

        $fields = "";
        $modelFields = array_keys($model->scaffold);
        $counter = count($modelFields);
        for ($i = 0; $i < $counter; $i++) {
            if ($i !== count($modelFields) - 1) {
                $fields .= "'{$modelFields[$i]}' => 'required',\n\t\t\t";
            } else {
                $fields .= "'{$modelFields[$i]}' => 'required',";
            }
        }
        
        $routes = str_replace(
            ['{class}', '{classes}', '{class_var}', '{class_vars}', '{namespace}', '{fields}'], 
            [$name, Inflect::pluralize($name), $property, Inflect::pluralize($property), env('APP_NAMESPACE', 'App'), $fields], 
            file_get_contents(__DIR__.'/temps/scaffold/controller.temp')
        );
        file_put_contents(
            path('app/Controllers/' . $name . 'Controller.php'),
            $routes
        );
    }
}