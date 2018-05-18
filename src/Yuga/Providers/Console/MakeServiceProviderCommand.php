<?php
namespace Yuga\Providers\Console;

use Yuga\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class MakeServiceProviderCommand extends Command
{
    protected $name = 'make:provider';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a service provider that can be located by your app ';

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