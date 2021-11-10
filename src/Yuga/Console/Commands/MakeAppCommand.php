<?php
namespace Yuga\Console\Commands;

use Yuga\Console\Command;
use Nette\PhpGenerator\PhpFile;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MakeAppCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Make a new Yuga command";

    protected $path = 'app/Commands';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $name = $this->argument('name');

        $this->createDirectories();
        $this->processCommand($name);
        $this->info('Command Created Successfully.');
    }

    /**
     * Process The command 
     * 
     * @param string $handler
     * @param string $method
     * @param string $eventName
     * 
     * @return void
     */
    protected function processCommand(string $name)
    {
        file_put_contents(
            path($this->path . '/' . trim($name) . '.php'),
            $this->generateCommand($name)
        );
        file_put_contents(
            path('config/AppCommands.php'),
            $this->processAppConfigFiles($name)
        );
    }

    /**
     * Generate Command logic
     * 
     * @param string $name
     * 
     * @return void
     */
    protected function generateCommand($name)
    {
        $file = new PhpFile;
        $file->addComment('This file was auto-generated.');

        $namespace = $file->addNamespace(env('APP_NAMESPACE', 'App'). '\\Commands');

        $namespace->addUse('Yuga\Console\Command');
        $namespace->addUse('Yuga\Interfaces\Commands\CommandInterface');
        $namespace->addUse('Symfony\Component\Console\Input\InputOption');
        $namespace->addUse('Symfony\Component\Console\Input\InputArgument');
        
        $class = $namespace->addClass(trim($name));
        $class->addImplement('Yuga\Interfaces\Commands\CommandInterface');
        $class->setExtends('Yuga\Console\Command');
        $classMethod = $class->addMethod('handle')->setBody('return $this->info("Successful");');
        $classMethod->addComment('@return mixed');
        $class->addProperty('name', str_replace('command', '', 'app:' . strtolower($name)))->setProtected()->addComment('@var string');
        $class->addProperty('description', 'This is a Yuga ' . $name . ' Command')->setProtected()->addComment('The console command description.')->addComment('@var string');
        $class->addProperty('help', 'This is help for the  Yuga ' . $name . ' Command')->setProtected()->addComment('The console command help info.')->addComment('@var string');
        $class->addProperty('path')->setProtected()->addComment('@var string');

        return $file;
    }

    protected function processAppConfigFiles($commandName)
    {
        $commands = [];
        if (\file_exists(path('config/AppCommands.php'))) {
            $commands = require path('config/AppCommands.php');
        }
        $commandToMake = env('APP_NAMESPACE', 'App') . '\\Commands\\' . $commandName;
        if (!in_array($commandToMake, $commands))
            $commands[] = $commandToMake;

        $generatedCommands = '[';
        foreach ($commands as $command) {
            $generatedCommands .= "\n\t\\". $command. "::class,";
        }
        $generatedCommands .= "\n];";

        $commandsFile = str_replace(
            '{commands}',
            $generatedCommands,
            file_get_contents(__DIR__ . '/temps/commands.temp')
        );

        return $commandsFile;
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