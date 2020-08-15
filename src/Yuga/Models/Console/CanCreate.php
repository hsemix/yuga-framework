<?php

namespace Yuga\Models\Console;

use Yuga\Support\Inflect;
use Yuga\Scaffold\Scaffold;
use Yuga\Database\Elegant\Model;

trait CanCreate
{
    protected function makeCreateForm(Model $model)
    {
        $name = \class_base($model);
        $inputs = "";
        $property = \strtolower($name);
        foreach ($model->scaffold as $fieldName => $type) {
            $fieldType = Scaffold::getFormType($type);
            $input = '<input name="'. $fieldName .'" type="'. $fieldType .'" class="form-control" />';

            if ($fieldType != 'password') {
                $input = '<input name="'. $fieldName .'" type="'. $fieldType .'" value="{{ old(\''. $fieldName .'\') }}" class="form-control" />';
            }
            if ($fieldType == 'textarea') {
                $input = '<textarea name="'. $fieldName .'" cols="10" rows="4" class="form-control"></textarea>';
            }

            $label = \ucfirst($fieldName);
            $inputs .= '<div class="form-group">
                    <label class="control-label">'. $label .'</label>
                    ' . $input . '
                    @if($errors->has("'. $fieldName .'"))
                        <span class="text-danger">{{ $errors->first("'. $fieldName .'") }}</span>
                    @endif
                </div>' . "\n\t\t\t\t";
        }
        
        $directory = path('resources/views/' . $property);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $creator = str_replace(
            ['{title}', '{inputs}', '{form-title}', '{route}'], 
            [$name, $inputs, 'Create', Inflect::pluralize($property)], 
            file_get_contents(__DIR__.'/temps/scaffold/create-form.temp')
        );
        $fileName = $directory . '/create.hax.php';
        if (file_exists($fileName) && !$this->option('force')) {
            if ($this->confirm("The [{$fileName}] view already exists. Do you want to replace it?")) {
                file_put_contents($fileName, $creator);
            }
        } else {
            file_put_contents($fileName, $creator);
        }
        
    }
}