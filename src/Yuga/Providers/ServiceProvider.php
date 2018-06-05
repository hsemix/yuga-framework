<?php
namespace Yuga\Providers;

use Yuga\Interfaces\Application\Application;
use Yuga\Interfaces\Providers\IServiceProvider;

abstract class ServiceProvider implements IServiceProvider
{
    /**
     * Register a service provider to the container and resolve it or later
     * 
     * @param \Yuga\Container\Container $app
     * 
     * @return \Yuga\Interfaces\Application\Application
     */
    public function register(Application $app)
    {
        return $this->load($app);
    }

    /**
     * Register a service provider to the container and resolve it or later
     * 
     * @param \Yuga\Container\Container $app
     * 
     * @return void
     */
    abstract public function load(Application $app);

    /**
     * Register the package's custom Yuga commands.
     *
     * @param  array  $commands
     * @return void
     */
    public function commands($commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        // To register the commands with Yuga, we will grab each of the arguments
        // passed into the method and listen for Yuga "start" an event which will
        // give us the Yuga console instance which we will give commands to.
        $events = $this->app['console.events'];

        $events->attach('yuga.start', function($yuga) use ($commands) {
            $yuga->resolveCommands($commands);
        });
    }
}