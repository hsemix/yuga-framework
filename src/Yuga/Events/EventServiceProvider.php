<?php
namespace Yuga\Events;

use Yuga\Console\Events\YugaStart;
use Yuga\Providers\ServiceProvider;
use Yuga\Interfaces\Application\Application;

class EventServiceProvider extends ServiceProvider
{
    public function load(Application $app)
    {
        $app->singleton('events', Event::class);
        $app->resolve('events');
        if ($app->runningInConsole()) {
            $app->singleton('console.events', function () use ($app) {
                return new YugaStart($app);
            });
        }
    }
}