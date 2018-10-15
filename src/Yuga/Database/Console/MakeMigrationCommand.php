<?php
namespace Yuga\Database\Console;

use Yuga\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

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
        $this->createDirectories();
        file_put_contents(
            path('database/migrations/' . $this->formatName() . '.php'),
            $this->compileMigrationTemp(trim($this->argument('name')))
        );

        file_put_contents(
            path('config/migrations.php'),
            $this->compileYugaMigrationsTemp(trim($this->argument('name')))
        );
        $this->info('migration table class created successfully.');
    }

    protected function compileMigrationTemp($model)
    {
        $table = strtolower($model);
        $migration = str_replace('{table}', $table, file_get_contents(__DIR__.'/temps/Migration.temp'));
        return str_replace(
            '{class}',
            'Create'.ucfirst($model).'Table',
            $migration
        );
    }

    /**
     * Format the name of the table given i.e. deCamalize it
     */
    protected function formatName()
    {
        $table = trim($this->argument('name'));
        $table = (new \DateTime)->format('YmdHis').'_create_'.strtolower($table).'_table';
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
        if (!in_array($table, $migrations))
            $migrations['migrate'][] = $table;
        

        $generatedMigrations = '[';
        foreach ($migrations['migrate'] as $migration) {
            $generatedMigrations .= "\n\t\t". $migration. "::class,";
        }
        $generatedMigrations .= "\n\t]";

        $generatedSeeds = '[';
        foreach ($migrations['seed'] as $seed) {
            $generatedSeeds .= "\n\t\t". $seed. "::class,";
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