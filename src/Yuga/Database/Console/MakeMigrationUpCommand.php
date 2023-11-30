<?php
namespace Yuga\Database\Console;

use Yuga\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Yuga\Database\Migration\MigrationServiceProvider;

class MakeMigrationUpCommand extends Command
{
    protected $name = 'migration:up';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run all migrations and create respective tables';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $migration = new MigrationServiceProvider();
        $migration->runUp($this->getYuga());
        $this->info('Migration was successful, check your database for confirmation.');
    }
}