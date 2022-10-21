<?php

namespace Yuga\Database\Console;

use Symfony\Component\Console\Input\InputArgument;
use Yuga\Console\Command;
use Yuga\Scaffold\Scaffold;

class MakeMigrationCommand extends Command
{
    protected $name = 'migration:make';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a database migration class that represents a table, Provide the Name of the table';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->processMigration();
        $this->info('migration table class created successfully.');
    }

    /**
     * Process Migration.
     *
     * @param string|null $name
     */
    public function processMigration($name = null, array $scaffold = null)
    {
        $this->createDirectories();
        file_put_contents(
            path('database/migrations/'.$this->formatName($name).'.php'),
            $this->compileMigrationTemp(trim($name ? $name : $this->argument('name')), $scaffold)
        );

        file_put_contents(
            path('config/migrations.php'),
            $this->compileYugaMigrationsTemp(trim($name ? $name : $this->argument('name')))
        );
    }

    protected function compileMigrationTemp($model, array $scaffold = null)
    {
        $table = strtolower($model);
        $migration = str_replace('{table}', $table, file_get_contents(__DIR__.'/temps/Migration.temp'));
        if ($scaffold) {
            if (count($scaffold) > 0) {
                $migration = $this->processMigrationTemp($table, $scaffold);
            }
        }

        return str_replace(
            '{class}',
            'Create'.ucfirst($model).'Table',
            $migration
        );
    }

    protected function processMigrationTemp($table, array $fields = [])
    {
        $scaffold = '';
        $i = 0;
        foreach ($fields as $field => $type) {
            $dataType = $this->processFieldType($type);

            if ($i != (count($fields) - 1)) {
                $scaffold .= '$'."table->column('".$field."')->".$dataType."->nullable();\n\t\t\t";
            } else {
                $scaffold .= '$'."table->column('".$field."')->".$dataType.'->nullable();';
            }
            $i++;
        }

        return str_replace(['{table}', '{scaffold_fields}'], [$table, $scaffold], file_get_contents(__DIR__.'/temps/MigrationScaffold.temp'));
    }

    protected function processFieldType($type)
    {
        return Scaffold::getMethod($type) == 'string' ? Scaffold::getMethod($type).'(255)' : Scaffold::getMethod($type).'()';
    }

    /**
     * Format the name of the table given i.e. deCamalize it.
     */
    protected function formatName($name = null)
    {
        $table = trim($name ? $name : $this->argument('name'));
        $table = (new \DateTime())->format('YmdHis').'_create_'.strtolower($table).'_table';

        return $table;
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function createDirectories()
    {
        if (!is_dir($directory = path('database/migrations'))) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the table'],
        ];
    }

    /**
     * Compiles the MiddlewWare temp.
     *
     * @return string
     */
    protected function compileYugaMigrationsTemp($name)
    {
        $migrations = require path('config/migrations.php');
        $table = 'Create'.ucfirst($name).'Table';
        if (!in_array($table, $migrations['migrate'])) {
            $migrations['migrate'][] = $table;
        }

        $generatedMigrations = '[';
        foreach ($migrations['migrate'] as $migration) {
            $generatedMigrations .= "\n\t\t".$migration.'::class,';
        }
        $generatedMigrations .= "\n\t]";

        $generatedSeeds = '[';
        foreach ($migrations['seed'] as $seed) {
            $generatedSeeds .= "\n\t\t".$seed.'::class,';
        }
        $generatedSeeds .= "\n\t]";

        $migration = str_replace(
            '{migrations}',
            $generatedMigrations,
            file_get_contents(__DIR__.'/temps/config.temp')
        );

        $migration = str_replace(
            '{seeds}',
            $generatedSeeds,
            $migration
        );

        return $migration;
    }
}
