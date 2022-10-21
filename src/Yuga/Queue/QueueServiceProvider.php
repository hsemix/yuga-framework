<?php

/**
 * @author Mahad Tech Solutions
 */

namespace Yuga\Queue;

use Yuga\Application\Application as App;
use Yuga\Interfaces\Application\Application;
use Yuga\Providers\ServiceProvider;
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
        // if ($app->runningInConsole()) {

        $app->singleton('queue', function () {
            return (new Queue(config('queue')))->connect();
        });
        // }
        // $this->commands(array_keys($this->queueCommands()));
    }

    public function boot()
    {
    }

    protected function queueCommands()
    {
        return [
            'command.queue.flush' => $this->app->singleton('command.queue.flush', function () {
                return new MakeQueueFlushCommand();
            }),
        ];
    }
}
