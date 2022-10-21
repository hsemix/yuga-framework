<?php

namespace Yuga\Models\Console;

use Yuga\Database\Console\MakeMigrationCommand;
use Yuga\Database\Elegant\Model;
use Yuga\Support\Inflect;

trait CreateMigrations
{
    /**
     * Make the scaffold for migrations.
     *
     * @param \Yuga\Database\Elegant\Model $model
     * @param mixed
     */
    protected function processMigrations(Model $model)
    {
        $name = \class_base($model);
        $property = \strtolower($name);
        $migration = new MakeMigrationCommand();
        $migration->processMigration(Inflect::pluralize($property), $model->scaffold);
    }
}
