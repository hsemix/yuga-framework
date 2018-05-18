<?php
namespace Yuga\Database\Migration;

use Yuga\Application;
use Yuga\Providers\ServiceProvider;

class MigrationServiceProvider extends ServiceProvider
{
    public function load(Application $app)
    {
        $config = $app->config->load('config.migrations');

        if (env('APP_RUN_MIGRATIONS', false) && !is_null(env('DATABASE_NAME'))) {
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
        foreach ($migrations as $migration) {
            if (class_exists($migration)) {
                $migration = new $migration('1.0');
                $migration->up();
            }
        }
    }

    protected function getMigrationPath()
    {
        return path().'database'.DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR;
    }
}