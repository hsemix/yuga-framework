<?php

namespace Yuga\Models\Console;

use Yuga\Database\Elegant\Model;
use Yuga\Scaffold\Scaffold;
use Yuga\Support\Inflect;

trait CanDisplay
{
    /**
     * Make the scaffold for a Index page.
     *
     * @param \Yuga\Database\Elegant\Model $model
     * @param mixed
     */
    protected function makeIndexForm(Model $model)
    {
        $name = \class_base($model);
        $menu = '';
        $property = \strtolower($name);
        $table = '';
        foreach ($model->scaffold as $fieldName => $type) {
            $fieldType = Scaffold::getFormType($type);
            $label = \ucfirst($fieldName);
            if ($fieldType != 'password') {
                $menu .= '<th>'.str_replace('_', ' ', $label).'</th>'."\n\t\t\t\t";
                $table .= '<td>{{ $'.$property.'->'.$fieldName.' }}</td>'."\n\t\t\t\t\t";
            }
        }
        $loop = '$'.Inflect::pluralize($property).' as $'.$property;
        $directory = path('resources/views/'.$property);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $creator = str_replace(
            ['{title}', '{menu}', '{model-id}', '{route}', '{table}', '{loop}'],
            [Inflect::pluralize($name), $menu, '$'.$property.'->'.$model->getPrimaryKey(), Inflect::pluralize($property), $table, $loop],
            file_get_contents(__DIR__.'/temps/scaffold/index.temp')
        );
        $fileName = $directory.'/index.hax.php';
        if (file_exists($fileName) && !$this->option('force')) {
            if ($this->confirm("The [{$fileName}] view already exists. Do you want to replace it?")) {
                file_put_contents($fileName, $creator);
            }
        } else {
            file_put_contents($fileName, $creator);
        }
    }
}
