<?php

namespace Yuga\Events;

use Yuga\Console\Events\YugaStart;
use Yuga\Providers\ServiceProvider;
use Yuga\Interfaces\Application\Application;
use Yuga\Interfaces\Events\Dispatcher as IDispatcher;

class EventServiceProvider extends ServiceProvider
{
    protected $app;
    /**
     * All Yuga default events registered here!
     * 
     * @var array $events
     */
    protected $events = [
        'on:app-start'      => \Yuga\EventHandlers\ApplicationStart::class,
        'on:app-stop'       => \Yuga\EventHandlers\ApplicationStop::class,
        'on:authenticate'   => \Yuga\EventHandlers\Auth\UserLoggedIn::class,
        'on:signup'         => \Yuga\EventHandlers\Auth\UserRegistered::class,
        'on:signout'        => \Yuga\EventHandlers\Auth\UserLoggedOut::class,
        'on:hax-render'     => \Yuga\EventHandlers\Hax\Rendered::class,
    ];

    /**
     * Boot the event service provider with $app 
     * 
     * @param \Yuga\Interfaces\Application\Application $app
     * 
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Load a service provider
     * 
     * @param \Yuga\Interfaces\Application\Application $app
     * 
     * @return mixed
     */
    public function load(Application $app)
    {
        $app->singleton('events', function ($app) {
            return $this->loadEvents(new Event($app));
        });
        if ($app->runningInConsole()) {
            $app->singleton('console.events', function () use ($app) {
                return new YugaStart($app);
            });
        }
    }
    
    /**
     * Load all event handlers that have been registered by the developer
     * 
     * @param \Yuga\Events\IDispatcher $dispatcher
     * 
     * @return \Yuga\Events\Event $dispatcher
     */
    protected function loadEvents(IDispatcher $dispatcher)
    {
        $otherApplicationEvents = [];
        if (\file_exists(path('config/AppEvents.php'))) {
            $otherApplicationEvents = require path('config/AppEvents.php');
        }
        $handlers = array_merge($this->events,  $otherApplicationEvents);

        foreach ($handlers as $event => $handles) {
            if (is_array($handles)) {
                foreach ($handles as $handle) {
                    if (is_array($handle)) {
                        $dispatcher->attach($event, [$this->app->resolve($handle[0]), $handle[1]]);
                    } else {
                        $dispatcher->attach($event, $this->app->resolve($handle));
                    }
                }
            } else {
                $dispatcher->attach($event, $this->app->resolve($handles));
            }
        }
        return $dispatcher;
    }
}