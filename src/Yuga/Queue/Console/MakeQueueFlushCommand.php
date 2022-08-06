<?php 

namespace Yuga\Queue\Console;

use Yuga\Console\CLI;
use Yuga\Queue\Queue;
use Yuga\Console\Command;

/**
 * Queue Table creation.
 */
class MakeQueueFlushCommand extends Command
{
	/**
	 * The Command's name
	 *
	 * @var string
	 */
	protected $name = 'queue:flush';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flushes all of the failed queue jobs';


	/**
	 * Creates a queue table
	 */
	public function handle()
	{
		
		$cli = new CLI;

		// print_r($cli);
        $cli->writeLine("Hello Semix Hamidouh", [CLI::BG_YELLOW, CLI::COLOR_RED, CLI::STYLE_BLINK]);

		// return (new Queue(config('queue')))->connect();
	}
}