<?php
namespace Yuga\Providers;

use Yuga\Interfaces\Application\Application;
use Yuga\Interfaces\Providers\IServiceProvider;

abstract class ServiceProvider implements IServiceProvider
{
    protected $app;
    
    /**
     * The paths that should be published.
     *
     * @var array
     */
    protected static $publishes = [];

    /**
     * Register a service provider to the container and resolve it or later
     * 
     * @param \Yuga\Interfaces\Application\Application $app
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
     * Register paths to be published by the publish command.
     *
     * @param  array  $paths
     * @param  string  $group
     * @return void
     */
    protected function publishes(array $paths, $group)
    {
        if (! array_key_exists($group, static::$publishes)) {
            static::$publishes[$group] = [];
        }

        static::$publishes[$group] = array_merge(static::$publishes[$group], $paths);
    }

    /**
     * Get the paths to publish.
     *
     * @param  string|null  $group
     * @return array
     */
    public static function pathsToPublish($group = null)
    {
        if (is_null($group)) {
            $paths = [];

            foreach (static::$publishes as $class => $publish) {
                $paths = array_merge($paths, $publish);
            }

            return array_unique($paths);
        } else if (array_key_exists($group, static::$publishes)) {
            return static::$publishes[$group];
        }

        return [];
    }

    /**
     * Register the package's custom Yuga commands.
     *
     * @param  array  $commands
     * @return void
     */
    public function commands(string|array $commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        // To register the commands with Yuga, we will grab each of the arguments
        // passed into the method and listen for Yuga "start" an event which will
        // give us the Yuga console instance which we will give commands to.
        $events = $this->app['console.events'];

        $events->attach('yuga.start', function($event, $yuga) use ($commands) {
            $yuga->resolveCommands($commands);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}