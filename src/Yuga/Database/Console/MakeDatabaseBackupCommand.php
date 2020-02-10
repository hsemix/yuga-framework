<?php
namespace Yuga\Database\Console;

use Yuga\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class MakeDatabaseBackupCommand extends Command
{
    protected $name = 'make:backup';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a database backup file for later use';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        
    }
}