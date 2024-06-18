<?php

namespace Yuga\Models\Console;

use Yuga\Support\Inflect;
use Yuga\Scaffold\Scaffold;
use Yuga\Database\Elegant\Model;

trait CanDelete
{
    /**
     * Make the scaffold for a delete form
     * 
     * @param \Yuga\Database\Elegant\Model $model
     * @param mixed
     */
    public function makeDeleteForm(Model $model)
    {
        $name = \class_base($model);
        $inputs = "";
        $property = \strtolower($name);
        foreach ($model->scaffold as $fieldName => $type) {

            $fieldType = Scaffold::getFormType($type);
            
            $label = \ucfirst($fieldName);
            if ($fieldType != 'password') {
                $inputs .= '<div class="col-sm-2">'. str_replace('_', ' ', $label) .'</div> <div class="col-sm-10">{{ $' . $property . '->' . $fieldName . ' }}</div>' . "\n\t\t\t";
            }
        }
        
        $directory = path('resources/views/' . $property);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $creator = str_replace(
            ['{title}', '{form}', '{model-id}', '{route}'], 
            [$name, $inputs, '$' . $property . '->' . $model->getPrimaryKey(), Inflect::pluralize($property)], 
            file_get_contents(__DIR__.'/temps/scaffold/delete.temp')
        );
        $fileName = $directory . '/delete.hax.php';
        if (file_exists($fileName) && !$this->option('force')) {
            if ($this->confirm("The [{$fileName}] view already exists. Do you want to replace it?")) {
                file_put_contents($fileName, $creator);
            }
        } else {
            file_put_contents($fileName, $creator);
        }
    }
}