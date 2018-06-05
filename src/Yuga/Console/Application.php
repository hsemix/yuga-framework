<?php
namespace Yuga\Console;

use Closure;
use Exception;
use Throwable;
use Yuga\Container\Container;
use Yuga\Interfaces\Events\Dispatcher;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Yuga\Application\Application as YugaApplication;
use Symfony\Component\Console\Application as Console;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Debug\Exception\FatalThrowableError;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class Application extends Console
{
    /**
     * The Event Dispatcher.
     *
     * @var \Yuga\Interfaces\Events\Dispatcher
     */
    protected $events;
    protected static $app;
    protected $yuga;

    public function __construct(Container $yuga, Dispatcher $events, $name, $version)
    {
        $this->yuga = $yuga;
        parent::__construct($name, $version);
        $this->events = $events;
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
     * @param  \Symfony\Component\Console\Command\Command  $command
     * @return \Symfony\Component\Console\Command\Command
     */
    protected function addToParent(SymfonyCommand $command)
    {
        return parent::add($command);
    }

    /**
     * Add a command to the console.
     *
     * @param  \Symfony\Component\Console\Command\Command  $command
     * @return \Symfony\Component\Console\Command\Command
     */
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

    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        $commandName = $this->getCommandName(
            $input = $input ?: new ArgvInput
        );

        $exitCode = parent::run($input, $output);
        
        return $exitCode;
    }
}