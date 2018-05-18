<?php
namespace Yuga\View\Console;

use Yuga\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class MakeViewModelCommand extends Command
{
    protected $name = 'make:viewmodel';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make an MVVM View Model easily (includes the view)';

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