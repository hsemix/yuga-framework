<?php 

namespace Yuga\Queue\Console;

use Yuga\Console\Command;

/**
 * Queue Table creation.
 */
class MakeQueueRetryCommand extends Command
{
	/**
	 * The Command's name
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
	 * Creates a queue table
	 */
	public function handle()
	{
        
	}
}