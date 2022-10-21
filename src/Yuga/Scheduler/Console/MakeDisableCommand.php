<?php

namespace Yuga\Scheduler\Console;

use Yuga\Console\Command;

/**
 * Disable Task Running.
 */
class MakeDisableCommand extends SchedulerCommand
{
    /**
     * The Command's name.
     *
     * @var string
     */
    protected $name = 'scheduler:disable';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disables the Scheduler runner.';

    /**
     * Disables task running.
     */
    public function handle()
    {
        $this->getConfig();

        //delete the file with json content
        @unlink($this->config['FilePath'].'/'.$this->config['FileName']);

        $this->disabled();
    }
}
