<?php

namespace Yuga\Cache\Console;

use Symfony\Component\Console\Input\InputArgument;
use Yuga\Console\Command;

class ForgetCommand extends Command
{
    protected $name = 'cache:forget';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove an item from the cache';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('The forget cache command.');
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
