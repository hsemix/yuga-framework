<?php
namespace Yuga\Http\Console;

use Yuga\Console\Command;
use Yuga\Http\Middleware\MiddleWare;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MakeMiddlewareCommand extends Command
{
    protected $name = 'make:middleware';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make A middleware class and register it globally';


    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->createDirectories();
        $this->makeMiddleware(ucfirst(trim($this->argument('name'))), trim($this->option('alias')));
        $alias = trim($this->option('alias'));
        if (!$alias) {
            $alias = strtolower(trim($this->argument('name')));
        }
        file_put_contents(
            path('config/AppMiddleware.php'),
            $this->compileYugaMiddlewareTemp(trim($this->argument('name')), $alias)
        );

        $this->info('Middleware created successfully.');
    }

    protected function makeMiddleware($name, $alias)
    {
        file_put_contents(
            path('app/Middleware/'. $name. '.php'),
            $this->compileMiddlewareTemp($name)
        );
    }

    /**
     * Compiles the Middleware temp.
     *
     * @return string
     */
    protected function compileMiddlewareTemp($name)
    {
        $middleware = str_replace('{namespace}', env('APP_NAMESPACE', 'App'), file_get_contents(__DIR__.'/temps/Middleware.temp'));
        return str_replace('{middleware}', $name, $middleware);
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function createDirectories()
    {
        if (!is_dir($directory = path('app/Middleware'))) {
            mkdir($directory, 0755, true);
        }
    }


    /**
     * Compiles the MiddlewWare temp.
     *
     * @return string
     */
    protected function compileYugaMiddlewareTemp($name, $alias)
    {
        $middleware = require path('config/AppMiddleware.php');
        $middleware[str_ireplace('middleware', '', $alias)] = env('APP_NAMESPACE', 'App').'\\Middleware\\'.$name;

        $generatedMiddleware = '[';
        foreach ($middleware as $alias => $ware) {
            $generatedMiddleware .= "\n\t'{$alias}' => \\". $ware. "::class,";
        }
        $generatedMiddleware .= "\n]";

        return str_replace(
            '{middleware}',
            $generatedMiddleware.';',
            file_get_contents(__DIR__.'/temps/AppMiddleware.temp')
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['alias', 'a', InputOption::VALUE_REQUIRED, 'Provide an alias for your middleware', null]
        ];
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
