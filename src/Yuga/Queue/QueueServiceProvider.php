<?php

/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Queue;

use Yuga\Providers\ServiceProvider;
use Yuga\Interfaces\Application\Application;
use Yuga\Application\Application as App;
use Yuga\Queue\Console\MakeQueueFlushCommand;

class QueueServiceProvider extends ServiceProvider
{
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function load(Application $app)
    {
        $app->singleton('queue', function () use($app) {
            return (new Queue(config('queue')))->connect();
        });
    }    
}