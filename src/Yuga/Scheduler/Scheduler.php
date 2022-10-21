<?php

namespace Yuga\Scheduler;

use Yuga\Console\Application as Console;
use Yuga\Interfaces\Application\Application;

class Scheduler
{
    /**
     * @var array
     */
    protected $tasks = [];

    /**
     * @var Application
     */
    public $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Returns the created Tasks.
     *
     * @return Job[]
     */
    public function getTasks(): array
    {
        return $this->tasks;
    }

    //--------------------------------------------------------------------

    /**
     * Schedules a closure to run.
     *
     * @param \Closure $func
     */
    public function call(\Closure $func)
    {
        return $this->createTask('closure', $func);
    }

    /**
     * Schedules a console command to run.
     *
     * @param string $command
     */
    public function command(string $command)
    {
        return $this->createTask('command', $command);
    }

    /**
     * Schedules a local function to be exec'd.
     *
     * @param string $command
     */
    public function shell(string $command)
    {
        return $this->createTask('shell', $command);
    }

    /**
     * Schedules an Event to trigger.
     *
     * @param mixed $name Name of the event to trigger
     */
    public function event($name)
    {
        return $this->createTask('event', $name);
    }

    /**
     * Schedules a cURL command to a remote URL.
     *
     * @param string $url
     */
    public function url(string $url)
    {
        return $this->createTask('url', $url);
    }

    //--------------------------------------------------------------------

    /**
     * @param string $type
     * @param mixed  $action
     *
     * @return Task
     */
    protected function createTask(string $type, $action)
    {
        $task = new Job($type, $action);
        $task->setApplication($this->app);
        $task->setConsole($this->app['yuga']);
        $this->tasks[] = $task;

        return $task;
    }
}
