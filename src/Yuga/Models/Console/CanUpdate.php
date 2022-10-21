<?php

namespace Yuga\Models\Console;

use Yuga\Database\Elegant\Model;
use Yuga\Scaffold\Scaffold;
use Yuga\Support\Inflect;

trait CanUpdate
{
    /**
     * Make the scaffold for a update form.
     *
     * @param \Yuga\Database\Elegant\Model $model
     * @param mixed
     */
    protected function makeUpdateForm(Model $model)
    {
        $name = \class_base($model);
        $inputs = '';
        $property = \strtolower($name);
        $isEditor = false;
        $editors = 0;

        foreach ($model->scaffold as $fieldName => $type) {
            $fieldType = Scaffold::getFormType($type);
            $input = '<input name="'.$fieldName.'" id="'.$fieldName.'" type="'.$fieldType.'" class="form-control" />';

            if ($fieldType != 'password') {
                $input = '<input name="'.$fieldName.'" id="'.$fieldName.'" type="'.$fieldType.'"  value="{{ $'.$property.'->'.$fieldName.' }}" class="form-control" />';
            }
            if ($fieldType == 'textarea') {
                $input = '<textarea name="'.$fieldName.'" id="'.$fieldName.'" cols="10" rows="4" class="form-control" placeholder="Type Something...">{{ $'.$property.'->'.$fieldName.' }}</textarea>';
            }

            if ($fieldType == 'editor') {
                $isEditor = true;
                $input = '<textarea name="'.$fieldName.'" id="editor" cols="10" rows="4" class="form-control editor">{{ $'.$property.'->'.$fieldName.' }}</textarea>';
                $editors += 1;
            }

            $label = \ucfirst($fieldName);
            $inputs .= '<div class="form-group">
                    <label class="control-label">'.$label.'</label>
                    '.$input.'
                    @if($errors->has("'.$fieldName.'"))
                        <span class="text-danger">{{ $errors->first("'.$fieldName.'") }}</span>
                    @endif
                </div>'."\n\t\t\t\t";
        }

        $script = '';
        if ($isEditor) {
            $element = ($editors > 1) ? '.editor' : '#editor';

            $script = "
            <script src=\"https://slmta.org/assets/js/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js\"></script>
            <script type=\"\"text/javascript\"\">
                $(function() {
                    $('".$element."').wysihtml5();
                });
            </script>
            ";
        }

        $directory = path('resources/views/'.$property);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $creator = str_replace(
            ['{title}', '{inputs}', '{form-title}', '{route}', '{scripts}'],
            [$name, $inputs, 'Edit', Inflect::pluralize($property), $script],
            file_get_contents(__DIR__.'/temps/scaffold/create-form.temp')
        );
        $fileName = $directory.'/edit.hax.php';
        if (file_exists($fileName) && !$this->option('force')) {
            if ($this->confirm("The [{$fileName}] view already exists. Do you want to replace it?")) {
                file_put_contents($fileName, $creator);
            }
        } else {
            file_put_contents($fileName, $creator);
        }
    }
}
