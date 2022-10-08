<?php

namespace Yuga\Providers\Console;

use Yuga\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

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
        $this->createDirectories();
        file_put_contents(
            path('app/Providers/'.trim($this->argument('name')).'.php'),
            $this->compileProviderTemp(trim($this->argument('name')))
        );
        file_put_contents(
            path('config/ServiceProviders.php'),
            $this->compileYugaProvidersTemp(trim($this->argument('name')))
        );
        $this->info('Service Provider created successfully.');
    }

    /**
     * Compile the provider and save it in the app's directory
     * 
     * @param string $providerName
     */
    protected function compileProviderTemp($providerName)
    {
        $provider = str_replace('{namespace}', env('APP_NAMESPACE', 'App'), file_get_contents(__DIR__.'/temps/ServiceProvider.temp'));
        return str_replace(
            '{class}',
            $providerName,
            $provider
        );
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function createDirectories()
    {
        if (!is_dir($directory = path('app/Providers'))) {
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
    

    /**
     * Compiles the MiddlewWare temp.
     *
     * @return string
     */
    protected function compileYugaProvidersTemp($name)
    {
        $providers = require path('config/ServiceProviders.php');

        $providerToMake = env('APP_NAMESPACE', 'App') . '\\Providers\\' . $name;

        if (!in_array($providerToMake, $providers))
            $providers[] = $providerToMake;

        $generatedProviders = '[';
        foreach ($providers as $provider) {
            $generatedProviders .= "\n\t\\" . $provider . "::class,";
        }
        $generatedProviders .= "\n]";

        return str_replace(
            '{providers}',
            $generatedProviders . ';',
            file_get_contents(__DIR__ . '/temps/config.temp')
        );
    }
}