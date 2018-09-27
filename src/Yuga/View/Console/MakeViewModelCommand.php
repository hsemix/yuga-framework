<?php
namespace Yuga\View\Console;

use Yuga\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

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
        $this->createDirectories();
        file_put_contents(
            path('app/ViewModels/'.trim($this->argument('name')).'.php'),
            $this->compileViewModelTemp(trim($this->argument('name')))
        );
        $this->makeView();
        $this->info('ViewModel created successfully.');
    }

    protected function compileViewModelTemp($viewModelName)
    {
        $viewModel = str_replace('{namespace}', env('APP_NAMESPACE', 'App'), file_get_contents(__DIR__.'/temps/ViewModel.temp'));
        return str_replace(
            '{class}',
            $viewModelName,
            $viewModel
        );
    }

    protected function makeView()
    {
        file_put_contents(
            path('resources/views/templates/'.str_replace('ViewModel', '', trim($this->argument('name'))).'.php'),
            $this->compileView(trim($this->argument('name')))
        );
    }
    protected function compileView($viewName)
    {
        $view = file_get_contents(__DIR__.'/temps/view.temp');
        $viewName = str_replace('ViewModel', '', $viewName);
        return str_replace(
            '{class}',
            strtolower($viewName),
            $view
        );
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function createDirectories()
    {
        if (!is_dir($directory = path('app/ViewModels'))) {
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