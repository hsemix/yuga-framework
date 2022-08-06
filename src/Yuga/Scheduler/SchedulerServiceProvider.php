<?php

/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Scheduler;

use Yuga\Providers\ServiceProvider;
use Yuga\Interfaces\Application\Application;

class SchedulerServiceProvider extends ServiceProvider
{
    protected $scheduler;

    public function load(Application $app)
    {
        if ($app->runningInConsole()) {
            $app->singleton('scheduler', function () use($app) {
                return new Scheduler($app);
            });
            $this->scheduler = $app->resolve('scheduler');
        }
    }

    public function boot()
    {
        $this->schedule($this->scheduler);
    }

    public function schedule(Scheduler $scheduler)
    {
        
    }
}