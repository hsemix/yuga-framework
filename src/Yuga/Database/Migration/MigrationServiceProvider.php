<?php

namespace Yuga\Database\Migration;

use Yuga\Providers\ServiceProvider;
use Yuga\Interfaces\Application\Application;

class MigrationServiceProvider extends ServiceProvider
{
    public function load(Application $app)
    {
        $config = $app->config->load('config.migrations');

        if (env('APP_RUN_MIGRATIONS', false) && !is_null(env('DATABASE_NAME')) && !$app->runningInConsole()) {
            if (count($config->get('migrate')) > 0) {
                foreach (glob($this->getMigrationPath()."*.php") as $migration) {
                    require_once $migration;
                }
                $this->runMigrations($config->get('migrate'));
            } 
        }    
    }

    protected function runMigrations($migrations)
    {
        $migrationsTable = require_once "000000_create_migrations_table.php";

        $migrationsTable->up();

        $allMigrations = glob($this->getMigrationPath()."*.php");

        foreach ($allMigrations as $index => $value) {
            $fileName = str_replace(path('database/migrations/'), '', $value);
            $migrationName = str_replace('.php', '', $fileName);
            
            if (! db('migrations')->where('migration', $migrationName)->first()) {

                $migration = new $migrations[$index];
                $migration->up();

                db('migrations')->insert([
                    'migration' => $migrationName,
                ]);
            }
        }

        // foreach ($migrations as $migration) {
        //     if (class_exists($migration)) {
        //         $migration = new $migration;
        //         $migration->up();
        //     }
        // }
    }

    public function runUp(Application $app)
    {
        $config = $app->config->load('config.migrations');

        if ($app->runningInConsole()) {
            if (count($config->get('migrate')) > 0) {
                foreach (glob($this->getMigrationPath()."*.php") as $migration) {
                    require_once $migration;
                }
                $this->runMigrations($config->get('migrate'));
            } 
        }    
    }

    public function rollBack(Application $app)
    {
        $config = $app->config->load('config.migrations');

        if ($app->runningInConsole()) {
            if (count($config->get('migrate')) > 0) {
                foreach (glob($this->getMigrationPath()."*.php") as $migration) {
                    require_once $migration;
                }
                $this->rollBackMigrations($config->get('migrate'));
            } 
        }    
    }

    protected function rollBackMigrations($migrations)
    {
        foreach ($migrations as $migration) {
            if (class_exists($migration)) {
                $migration = new $migration;
                $migration->down();
            }
        }
    }

    public function seed(Application $app)
    {
        $config = $app->config->load('config.migrations');

        if ($app->runningInConsole()) {
            if (count($config->get('migrate')) > 0) {
                foreach (glob($this->getMigrationPath()."*.php") as $migration) {
                    require_once $migration;
                }
                $this->runSeeders($config->get('migrate'));
            } 
        }    
    }

    protected function runSeeders($migrations)
    {
        foreach ($migrations as $migration) {
            if (class_exists($migration)) {
                $migration = new $migration;
                $migration->seeder();
            }
        }
    }

    protected function getMigrationPath()
    {
        return path().'database'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR;
    }
}