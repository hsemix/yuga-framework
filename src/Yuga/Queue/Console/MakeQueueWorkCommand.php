<?php 

namespace Yuga\Queue\Console;

// use CodeIgniter\CLI\BaseCommand;
// use CodeIgniter\CLI\CLI;

use Yuga\Console\CLI;
use Yuga\Carbon\Carbon;
use Yuga\Console\Command;
use Yuga\Queue\Exceptions\QueueException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Generates a skeleton migration file.
 */
class MakeQueueWorkCommand extends Command
{
	/**
	 * The Command's Group
	 *
	 * @var string
	 */
	protected $group = 'Queue';

	/**
	 * The Command's Name
	 *
	 * @var string
	 */
	protected $name = 'queue:work';

	/**
	 * The Command's Description
	 *
	 * @var string
	 */
	protected $description = 'Works the queue.';

	/**
	 * The Command's Usage
	 *
	 * @var string
	 */
	protected $usage = 'queue:work';

	/**
	 * The Command's Options
	 *
	 * @var array
	 */
	protected $options = [
		'--queue' => 'The name of the queue to work, if not specified it will work the default queue',
	];

	/**
	 * Actually execute a command.
	 *
	 * @param array $params
	 */
    public function handlex()
    {
        $cli = new CLI;
        $queue = $this->option('queue') ?? config('queue')['default'];
        $cli->writeLine('Working Queue: ' . $queue, 'yellow');

        $response      = true;
		$jobsProcessed = 0;
		$startTime     = time();

        do {
			try {
				$this->stopIfNecessary($startTime, $jobsProcessed);

				$response = $this->yuga['queue']->fetch([$this, 'fire'], $queue);

				$jobsProcessed++;
			} catch (\Throwable $e) {
				$cli->writeLine('Failed', 'red');
				$cli->writeLine("Exception: {$e->getCode()} - {$e->getMessage()}\nfile: {$e->getFile()}:{$e->getLine()}");
			}
		}
		while($response === true);

		$cli->writeLine('Completed Working Queue', 'green');
    }

	public function handle()
    {
        $cli = new CLI;
        $queue = $this->option('queue') ?? config('queue')['default'];
        $cli->writeLine('Working Queue: ' . $queue, 'yellow');

        $response      = true;
		$jobsProcessed = 0;
		$startTime     = time();

        // while(true) {
		do {
			try {
				// $this->stopIfNecessary($startTime, $jobsProcessed);

				$response = $this->yuga['queue']->fetch([$this, 'fire'], $queue);

				// echo $response;
				$jobsProcessed++;
			} catch (\Throwable $e) {
				$cli->writeLine('Failed', 'red');
				$cli->writeLine("Exception: {$e->getCode()} - {$e->getMessage()}\nfile: {$e->getFile()}:{$e->getLine()}");
			}
		}
		while($response === true);

		$cli->writeLine('Completed Working Queue', 'green');
    }

	/**
	 * work an individual item in the queue.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function fire($data)
	{
		$cli = new CLI;
		
		// $data
		if ($data['job'] == 'Yuga\Queue\CallQueuedHandler@call') {
			$job = unserialize($data['data']['job']);
			$cli->writeLine('Running Job #' . $data['data']['jobName'], 'yellow');
			$app = app()->call([$job, 'run']);
			$cli->writeLine('Finished Job #' . $data['data']['jobName'], 'green');
		} else {
			$cli->writeLine('Failed to run Job', 'red');
		}
	}

	/**
	 * Determine if we should stop the worker.
	 *
	 * @param integer $startTime
	 * @param integer $jobsProcessed
	 */
	protected function stopIfNecessary($startTime, $jobsProcessed)
	{
        $cli = new CLI;
		$shouldQuit = false;

		$maxTime = ini_get('max_execution_time') - 5; //max execution time minus a bit of a buffer (5 sec).

		$maxMemory   = ($this->getMemoryLimit() / 1024 / 1024) - 10; //max memory with a buffer (10MB);
		$memoryUsage = memory_get_usage(true) / 1024 / 1024;

		$maxBatch = config('queue')['settings']['maxWorkerBatch'];

		//max time limit.
		if ($maxTime > 0 && time() - $startTime > $maxTime) {
			$shouldQuit = true;
			$reason     = 'Time Limit Reached';
		}
		//max memory
		else if ($maxMemory > 0 && $memoryUsage > $maxMemory) {
			$shouldQuit = true;
			$reason     = 'Memory Limit Reached';
		} else if ($maxBatch > 0 && $jobsProcessed >= $maxBatch) {
			$shouldQuit = true;
			$reason     = 'Maximum Batch Size Reached';
		}

		if (isset($reason)) {
			$cli->writeLine('Exiting Worker: ' . $reason, 'yellow');
		}

		return true;
	}
	/**
	 * calculate the memory limit
	 *
	 * @return integer memory limit in bytes.
	 */
	protected function getMemoryLimit()
	{
		$memory_limit = ini_get('memory_limit');

		//if there is no memory limit just set it to 2GB
		if($memory_limit = -1)
			return 2 * 1024 * 1024 * 1024;

		preg_match('/^(\d+)(.)$/', $memory_limit, $matches);

		if (!isset($matches[2]))
			throw new \Exception('Unknown Memory Limit');

		switch($matches[2])
		{
			case 'G' :
				$memoryLimit = $matches[1] * 1024 * 1024 * 1024;
				break;
			case 'M' :
				$memoryLimit = $matches[1] * 1024 * 1024;
				break;
			case 'K' :
				$memoryLimit = $matches[1] * 1024;
				break;
			default :
				throw new \Exception('Unknown Memory Limit');

			return $memoryLimit;
		}
	}

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['connection', InputArgument::OPTIONAL, 'The name of connection', null],
		];
    }

	/**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['queue',  null, InputOption::VALUE_OPTIONAL, 'The queue to listen on'],
        ];
    }
}
