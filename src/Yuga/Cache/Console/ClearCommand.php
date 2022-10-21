<?php

namespace Yuga\Cache\Console;

use Symfony\Component\Console\Input\InputArgument;
use Yuga\Console\Command;

class ClearCommand extends Command
{
    protected $name = 'cache:clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flush the Application cache';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('The clear cache command.');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the class'],
        ];
    }
}
