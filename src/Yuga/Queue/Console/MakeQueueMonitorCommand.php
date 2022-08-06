<?php 

namespace Yuga\Queue\Console;

use Yuga\Console\Command;

/**
 * Queue Table creation.
 */
class MakeQueueMonitorCommand extends Command
{
	/**
	 * The Command's name
	 *
	 * @var string
	 */
	protected $name = 'queue:monitor';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitors the Queue Worker execution';


	/**
	 * Creates a queue table
	 */
	public function handle()
	{
        
	}
}