<?php 

namespace Yuga\Queue\Console;

use Yuga\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputOption;

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
	protected $name = 'queue:watch';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Watches the Queue Worker execution';


	/**
	 * Creates a queue table
	 */
	public function handle()
	{
		$logger = "> /dev/null 2> /dev/null &";
		if (null !== $this->option('log')) {
			$logger = ' > ' . storage('logs/queue/' . $this->option('log')) . ' 2> ' . storage('logs/queue/' . $this->option('log')) . ' &';

			if (!is_dir($directory = storage('logs/queue'))) {
				mkdir($directory, 0755, true);
			}
		}
		
		$this->line("\n");
		$this->comment('**** Starting a watching Process... ****');
        $this->line("\n");
		file_put_contents(
			path('queue.sh'),
			str_replace(DIRECTORY_SEPARATOR, '/', str_replace(
				'{command}',
				path('yuga queue:work'),
				file_get_contents(__DIR__ . '/temps/queue-watch.temp')
			))
		);

		proc_open(path("queue.sh") . $logger, [STDIN, STDOUT, STDOUT], $pipes);

		$this->info('Watcher Started');
	}

	/**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['log',  'l', InputOption::VALUE_OPTIONAL, 'File to log responses to'],
        ];
    }
}