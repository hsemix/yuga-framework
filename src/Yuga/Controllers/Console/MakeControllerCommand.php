<?php
namespace Yuga\Controllers\Console;

use Yuga\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class MakeControllerCommand extends Command
{
    protected $name = 'make:controller';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a controller that extends the BaseController easily';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->createDirectories();
        file_put_contents(
            path('app/Controllers/'.trim($this->argument('name')).'.php'),
            $this->compileControllerTemp(trim($this->argument('name')))
        );

        $this->info('Controller created successfully.');
    }

    protected function compileControllerTemp($controllerName)
    {
        $controller = str_replace('{namespace}', env('APP_NAMESPACE', 'App'), file_get_contents(__DIR__.'/temps/Controller.temp'));
        return str_replace(
            '{class}',
            $controllerName,
            $controller
        );
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function createDirectories()
    {
        if (!is_dir($directory = path('app/Controllers'))) {
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
