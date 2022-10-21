<?php

namespace Yuga\Scheduler\Console;

use Yuga\Console\Command;

/**
 * Enables Task Running.
 */
class MakeEnableCommand extends SchedulerCommand
{
    /**
     * The Command's name.
     *
     * @var string
     */
    protected $name = 'scheduler:enable';

    /**
     * the Command's short description.
     *
     * @var string
     */
    protected $description = 'Enables the scheduler runner.';

    /**
     * the Command's usage.
     *
     * @var string
     */
    // protected $usage = 'cronjob:enable';

    /**
     * Enables task running.
     *
     * @param array $params
     */
    public function handle()
    {
        $settings = $this->saveSettings('enabled');

        if ($settings) {
            $this->enabled();
        } else {
            $this->alreadyEnabled();
        }
    }
}
