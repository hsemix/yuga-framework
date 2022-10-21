<?php

namespace Yuga\Queue\Console;

use Yuga\Console\CLI;
use Yuga\Console\Command;

/**
 * Queue Table creation.
 */
class MakeQueueRetryCommand extends Command
{
    /**
     * The Command's name.
     *
     * @var string
     */
    protected $name = 'queue:retry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retries a failed queue job';

    /**
     * Creates a queue table.
     */
    public function handle()
    {
        $cli = new CLI();
        $cli->writeLine('Resetting Failed Jobs Queue...', 'yellow');

        $queue = $this->yuga['queue'];

        $queue->reset();

        $cli->writeLine('Completed Resetting Failed Jobs the queue can now retry them', 'green');
    }
}
