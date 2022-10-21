<?php

namespace Yuga\Database\Console;

use Yuga\Console\Command;
use Yuga\Database\Migration\MigrationServiceProvider;

class MakeMigrationSeedCommand extends Command
{
    protected $name = 'migration:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate the database tables with some data provided in the seed method';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $migration = new MigrationServiceProvider();
        $migration->seed($this->getYuga());
        $this->info('Migration was successful, check your database for confirmation.');
    }
}
