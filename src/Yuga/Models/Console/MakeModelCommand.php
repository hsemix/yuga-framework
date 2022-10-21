<?php

namespace Yuga\Models\Console;

use Symfony\Component\Console\Input\InputArgument;
use Yuga\Console\Command;

class MakeModelCommand extends Command
{
    protected $name = 'make:model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make an Elegant Model easily';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->createDirectories();
        file_put_contents(
            path('app/Models/'.trim($this->argument('name')).'.php'),
            $this->compileModelTemp(trim($this->argument('name')))
        );

        $this->info('Model created successfully.');
    }

    protected function compileModelTemp($modelName)
    {
        $model = str_replace('{namespace}', env('APP_NAMESPACE', 'App'), file_get_contents(__DIR__.'/temps/Model.temp'));

        return str_replace(
            '{class}',
            $modelName,
            $model
        );
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function createDirectories()
    {
        if (!is_dir($directory = path('app/Models'))) {
            mkdir($directory, 0755, true);
        }
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
