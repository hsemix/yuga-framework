<?php

namespace Yuga\Models\Console;

use Yuga\Support\Inflect;
use Yuga\Database\Elegant\Model;
use Yuga\Database\Console\MakeMigrationCommand;

trait CreateMigrations
{
    protected function processMigrations(Model $model)
    {
        $name = \class_base($model);
        $property = \strtolower($name);
        $migration = new MakeMigrationCommand();
        $migration->processMigration(Inflect::pluralize($property), $model->scaffold);
    }
}