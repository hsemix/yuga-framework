<?php

namespace Yuga\Queue\Console;

use Yuga\Console\Command;

/**
 * Queue Table creation.
 */
class MakeQueueForgetCommand extends Command
{
    /**
     * The Command's name.
     *
     * @var string
     */
    protected $name = 'queue:forget';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes a failed queue job';

    /**
     * Creates a queue table.
     */
    public function handle()
    {
    }
}
