<?php

namespace Yuga\Models\Console;

use Yuga\Support\Inflect;
use Yuga\Scaffold\Scaffold;
use Yuga\Database\Elegant\Model;

trait CanShowDetails
{
    protected function makeDetailsForm(Model $model)
    {
        $name = \class_base($model);
        $inputs = "";
        $property = \strtolower($name);
        foreach ($model->scaffold as $fieldName => $type) {

            
            
            $fieldType = Scaffold::getFormType($type);
            
            $label = \ucfirst($fieldName);
            if ($fieldType != 'password') {
                $inputs .= '<dt class="col-sm-2">'. str_replace('_', ' ', $label) .'</dt> <dd class="col-sm-10">{{ $' . $property . '->' . $fieldName . ' }}</dd>' . "\n\t\t\t";
            }
        }
        
        $directory = path('resources/views/' . $property);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $creator = str_replace(
            ['{title}', '{form}', '{model-id}', '{route}'], 
            [$name, $inputs, '$' . $property . '->' . $model->getPrimaryKey(), Inflect::pluralize($property)], 
            file_get_contents(__DIR__.'/temps/scaffold/details.temp')
        );
        $fileName = $directory . '/details.hax.php';
        if (file_exists($fileName) && !$this->option('force')) {
            if ($this->confirm("The [{$fileName}] view already exists. Do you want to replace it?")) {
                file_put_contents($fileName, $creator);
            }
        } else {
            file_put_contents($fileName, $creator);
        }
    }
}