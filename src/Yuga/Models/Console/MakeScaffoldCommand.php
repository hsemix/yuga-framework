<?php
namespace Yuga\Models\Console;

use Yuga\Console\Command;
use Yuga\Support\FileLocator;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MakeScaffoldCommand extends Command
{
    use CanCreate, CanUpdate, CanShowDetails, CanDelete, CanDisplay, CreateRoutes, CreateControllers, CreateMigrations;
    protected $name = 'scaffold';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a scaffold using Elegant Model composition';

    /**
     * The console command Help text
     */
    protected $help = "This command creates a number of files for you, they include:\n\t<info>*Views</info>\n\t<info>*Controllers</info>\n\t<info>*Routes</info>\n\t<info>*Migrations</info>\nBased on how you define your model";

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $modelStrings = $this->input->getOption('models');
        $directory = $this->input->getOption('dir');
        if ($modelStrings == 'all') {
            $path = path() . 'app' . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR;
            foreach (glob($path . '*.php') as $model) {
                require_once $model;
            }
            $models = (new FileLocator)->getClassesOfNamespace(env('APP_NAMESPACE', 'App'). '\\' . $directory);
        } else {
            $models = \explode(',', $modelStrings);
            $models = array_map(function ($model) {
                return env('APP_NAMESPACE', 'App') . '\\Models\\' . $model;
            }, $models);
        }

        $this->formsCreator($models);
        $this->info('scaffold was made successfully.');
    }

    protected function formsCreator(array $models = [])
    {
        foreach ($models as $model) {
            $modelInstance = new $model;
            if (property_exists($modelInstance, 'scaffold')) {
                // make all the create forms
                $this->makeCreateForm($modelInstance);
                // make all the update forms
                $this->makeUpdateForm($modelInstance);
                // make all the details pages
                $this->makeDetailsForm($modelInstance);
                // make all the index pages
                $this->makeIndexForm($modelInstance);
                // make all the delete pages
                $this->makeDeleteForm($modelInstance);
                // process routes
                $this->processRoutes($modelInstance);
                // process controllers
                $this->processControllers($modelInstance);
                // process migrations
                $this->processMigrations($modelInstance);
            }
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['models', null, InputOption::VALUE_OPTIONAL, 'The names of all models whose scaffold are to be created (separated by commas).', 'all'],
            ['dir', null, InputOption::VALUE_OPTIONAL, 'The name of the folder where your models reside inside of the app folder.', 'Models'],
            ['force', null, InputOption::VALUE_OPTIONAL, 'Overwrite existing files.', false], 
        ];
    }
}
