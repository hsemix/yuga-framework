<?php
namespace Yuga\Events;

use Yuga\Application;
use Yuga\Console\Events\YugaStart;
use Yuga\Providers\ServiceProvider;

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