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
        if (env('RUN_DB_BACKUP', false)) {
            $this->scheduler->command('db:backup')->daily('4:30 am')->runInBackground();
        }
        
        if (env('RUN_QUEUE_WORKER', false)) {
            $this->scheduler->command('queue:work --stop')->everyMinute()->withoutOverlapping()->runInBackground();
        }
        
        $this->schedule($this->scheduler);
    }

    public function schedule(Scheduler $scheduler)
    {
        
    }
}