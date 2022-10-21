<?php

namespace Yuga\Scheduler\Console;

use Symfony\Component\Console\Input\InputOption;
use Yuga\Console\Command;
use Yuga\Scheduler\JobRunner;

/**
 * Runs current tasks.
 */
class MakeRunCommand extends SchedulerCommand
{
    /**
     * The Command's name.
     *
     * @var string
     */
    protected $name = 'scheduler:run';

    /**
     * the Command's short description.
     *
     * @var string
     */
    protected $description = 'Runs tasks based on the schedule, should be configured as a crontask to run every minute.';

    /**
     * the Command's usage.
     *
     * @var string
     */
    // protected $usage = 'cronjob:run [options]';

    /**
     * the Command's option.
     *
     * @var array
     */
    protected $options = ['-testTime' => 'Set Date to run script'];

    /**
     * Runs tasks at the proper time.
     *
     * @param array $params
     */
    public function handle()
    {
        $this->getConfig();
        $settings = $this->getSettings();

        if (!$settings || (isset($settings->status) && $settings->status !== 'enabled')) {
            $this->tryToEnable();

            return false;
        }

        $this->line("\n");
        $this->line('**** Running Tasks... ****');
        $this->line("\n");

        $runner = new JobRunner();
        $runner->command = $this;

        $testTime = $this->input->getOption('testTime');
        if ($testTime) {
            $runner->withTestTime($testTime);
        }
        $runner->run();

        $this->line("\n");
        $this->line('**** Completed ****');
        $this->line("\n");
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['testTime', null, InputOption::VALUE_OPTIONAL, 'Set Date to run script (When you want the scheduler to start).', null],
        ];
    }
}
