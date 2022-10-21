<?php

namespace Yuga\Queue\Console;

use Yuga\Console\Command;

/**
 * Queue Table creation.
 */
class MakeQueueTableCommand extends Command
{
    /**
     * The Command's name.
     *
     * @var string
     */
    protected $name = 'queue:table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a migration for the queue jobs database table';

    /**
     * Creates a queue table.
     */
    public function handle()
    {
    }
}
