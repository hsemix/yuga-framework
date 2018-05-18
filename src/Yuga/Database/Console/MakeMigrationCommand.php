<?php
namespace Yuga\Database\Console;

use Yuga\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class MakeMigrationCommand extends Command
{
    protected $name = 'migration:make';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a database migration class that represents a table';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        
    }

    
    
    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['views', null, InputOption::VALUE_OPTIONAL, 'Only Create authentication views.', false],
            ['force', null, InputOption::VALUE_OPTIONAL, 'Overwrite existing files.', false],
        ];
    }
}