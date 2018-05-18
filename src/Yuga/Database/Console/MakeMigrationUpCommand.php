<?php
namespace Yuga\Database\Console;

use Yuga\Console\Command;
use Symfony\Component\Console\Input\InputOption;

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