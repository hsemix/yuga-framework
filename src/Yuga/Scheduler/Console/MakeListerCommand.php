<?php

namespace Yuga\Scheduler\Console;

use Yuga\Console\Command;

/**
 * Lists currently scheduled tasks.
 */
class MakeListerCommand extends SchedulerCommand
{
    /**
     * The Command's name.
     *
     * @var string
     */
    protected $name = 'scheduler:list';

    /**
     * the Command's short description.
     *
     * @var string
     */
    protected $description = 'Lists the jobs currently set to run.';

    /**
     * Lists upcoming tasks.
     *
     * @param void
     */
    public function handle()
    {
        $this->getConfig();
        $settings = $this->getSettings();

        if (!$settings || (isset($settings->status) && $settings->status !== 'enabled')) {
            $this->tryToEnable();

            return false;
        }

        $scheduler = app('scheduler');

        $tasks = [];

        foreach ($scheduler->getTasks() as $task) {
            $cron = \Cron\CronExpression::factory($task->getExpression());
            $nextRun = $cron->getNextRunDate()->format('Y-m-d H:i:s');

            $tasks[] = [
                'name'     => $task->name ?: $task->getAction(),
                'type'     => $task->getType(),
                'next_run' => $nextRun,
            ];
        }

        usort($tasks, function ($a, $b) {
            return ($a['next_run'] < $b['next_run']) ? -1 : 1;
        });

        $this->table(['Name', 'Type', 'Next Run'], $tasks);
    }
}
