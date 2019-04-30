<?php
namespace Yuga\Events\Console;

use Yuga\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class MakeEventCommand extends Command
{
    protected $name = 'make:event';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make an Event to be triggered in your application';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->createDirectories();
        file_put_contents(
            path('app/Events/'.trim($this->argument('name')).'.php'),
            $this->compileEventTemp(trim($this->argument('name')))
        );

        $this->info('Event created successfully.');
    }

    protected function compileEventTemp($eventName)
    {
        $event = str_replace('{namespace}', env('APP_NAMESPACE', 'App'), file_get_contents(__DIR__.'/temps/Event.temp'));
        $event = str_replace('{event}', strtolower(str_ireplace('Event', '', $eventName)), $event);
        return str_replace(
            '{class}',
            $eventName,
            $event
        );
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function createDirectories()
    {
        if (!is_dir($directory = path('app/Events'))) {
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