<?php 

namespace Yuga\Queue\Console;

use Yuga\Console\Command;

/**
 * Queue Table creation.
 */
class MakeQueueRestartCommand extends Command
{
	/**
	 * The Command's name
	 *
	 * @var string
	 */
	protected $name = 'queue:restart';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restarts queue worker daemons after their current job';


	/**
	 * Creates a queue table
	 */
	public function handle()
	{
        
	}
}