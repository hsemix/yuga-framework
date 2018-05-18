<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Providers;

use Yuga\Application;
use Yuga\Support\IServiceProvider;

abstract class ServiceProvider implements IServiceProvider
{
    public function register(Application $app)
    {
        return $this->load($app);
    }
    abstract public function load(Application $app);

    /**
     * Register the package's custom Forge commands.
     *
     * @param  array  $commands
     * @return void
     */
    public function commands($commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        // To register the commands with Forge, we will grab each of the arguments
        // passed into the method and listen for Forge "start" event which will
        // give us the Forge console instance which we will give commands to.
        $events = $this->app['console.events'];

        $events->attach('yuga.start', function($yuga) use ($commands)
        {
            $yuga->resolveCommands($commands);
        });
    }
}