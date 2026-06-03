<?php

namespace Yuga\Console;

use Yuga\Container\Container;
use Yuga\Interfaces\Events\Dispatcher;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\InputInterface;
use Yuga\Application\Application as YugaApplication;
use Symfony\Component\Console\Application as Console;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class Application extends Console
{
    protected static $app;

    public function __construct(protected \Yuga\Container\Container $yuga, /**
     * The Event Dispatcher.
     */
    protected \Yuga\Interfaces\Events\Dispatcher $events, $name, $version)
    {
        parent::__construct($name, $version);
        if (!static::$app) {
            static::$app = $this;
        }
        $this->events->dispatch('yuga.start', [$this]);
    }
    /**
     * Create and boot a new Console application.
     *
     * @param  \Yuga\Interfaces\Application\Application  $app
     * @return \Yuga\Console\Application
     */
    public static function start($app)
    {
        return static::make($app);
    }

    public static function getInstance()
    {
        return static::$app;
    }

    /**
     * Create a new Console application.
     *
     * @param  \Yuga\Interfaces\Application\Application  $app
     * @return \Yuga\Console\Application
     */
    public static function make($app)
    {
        $console = new static(YugaApplication::getInstance(), $app['console.events'], 'Yuga Framework', $app::VERSION);

        $app->singleton('yuga', $console);

        return $console;
    }

    /**
     * Add the command to the parent instance.
     *
     * @return \Symfony\Component\Console\Command\Command
     */
    protected function addToParent(SymfonyCommand $command)
    {
        return parent::add($command);
    }

    /**
     * Add a command to the console.
     *
     * @return \Symfony\Component\Console\Command\Command
     */
    #[\Override]
    public function add(SymfonyCommand $command)
    {
        if ($command instanceof Command) {
            $command->setContainer($this->yuga);
        }

        return $this->addToParent($command);
    }

    /**
     * Add a command, resolving through the application.
     *
     * @param  string  $command
     * @return \Symfony\Component\Console\Command\Command
     */
    public function resolve($command)
    {
        return $this->add($this->yuga[$command]);
    }

    /**
     * Resolve an array of commands through the application.
     *
     * @param  array|mixed  $commands
     * @return void
     */
    public function resolveCommands($commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        foreach ($commands as $command)  {
            $this->resolve($command);
        }
    }

    #[\Override]
    public function run(?InputInterface $input = null, ?OutputInterface $output = null)
    {
        $this->getCommandName(
            $input = $input ?: new ArgvInput
        );
        return parent::run($input, $output);
    }

    /**
     * Run an Yuga console command by name.
     *
     * @param  string  $command
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    public function call($command, array $parameters = [], ?OutputInterface $output = null)
    {
        $parameters['command'] = $command;

        // Unless an output interface implementation was specifically passed to us we
        // will use the "NullOutput" implementation by default to keep any writing
        // suppressed so it doesn't leak out to the browser or any other source.
        $output ??= new NullOutput;

        $input = new ArrayInput($parameters);

        return $this->find($command)->run($input, $output);
    }
}
