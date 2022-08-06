<?php 

namespace Yuga\Queue\Console;

use Yuga\Queue\Job;
use Yuga\Queue\Worker;
use Yuga\Carbon\Carbon;
use Yuga\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Queue Table creation.
 */
class MakeQueueWorkCommand extends Command
{
	/**
	 * The Command's name
	 *
	 * @var string
	 */
	protected $name = 'queue:work';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Processes the next job in the Queue';

	/**
     * The queue worker instance.
     *
     * @var \Yuga\Queue\Worker
     */
    protected $worker;

	/**
     * Create a new queue listen command.
     *
     * @param  \Yuga\Queue\Worker  $worker
     * @return void
     */
    public function __construct(Worker $worker)
    {
        parent::__construct();

        $this->worker = $worker;
    }


	/**
	 * Runs the worker
	 */
	public function handle()
	{
        $daemon = $this->option('daemon');

		$this->listenForEvents();

		$config = config('queue');

        $connection = $this->argument('connection') ?: $config['default'];

        $delay = $this->option('delay');

        $memory = $this->option('memory');

        $queue = $this->option('queue') ? $this->option('queue') : $config['connections'][$connection] ?? 'wait';

        $this->runWorker($connection, $queue, $delay, $memory, $daemon);
	}

	/**
     * Run the worker instance.
     *
     * @param  string  $connection
     * @param  string  $queue
     * @param  int  $delay
     * @param  int  $memory
     * @param  bool  $daemon
     * @return array
     */
    protected function runWorker($connection, $queue, $delay, $memory, $daemon = false)
    {
        // $this->worker->setDaemonExceptionHandler(
        //     $this->container['Yuga\Exceptions\HandlerInterface']
        // );

        $sleep = $this->option('sleep');
        $tries = $this->option('tries');

        if (!$daemon) {
            return $this->worker->runNextJob($connection, $queue, $delay, $sleep, $tries);
        }

        return $this->worker->daemon($connection, $queue, $delay, $memory, $sleep, $tries);
    }

	/**
     * Listen for the queue events in order to update the console output.
     *
     * @return void
     */
    protected function listenForEvents()
    {
        $events = $this->yuga['events'];

        $events->on('yuga.queue.processing', function ($connection, $job) {
            $this->writeOutput($job, 'starting');
        });

        $events->on('yuga.queue.processed', function ($connection, $job) {
            $this->writeOutput($job, 'success');
        });

        $events->on('yuga.queue.failed', function ($connection, $job) {
            $this->writeOutput($job, 'failed');
        });
    }

	/**
     * Write the status output for the queue worker.
     *
     * @param  \Yuga\Queue\Job  $job
     * @param  string  $status
     * @return void
     */
    protected function writeOutput(Job $job, $status)
    {
        switch ($status) {
            case 'starting':
                return $this->writeStatus($job, 'Processing', 'comment');
            case 'success':
                return $this->writeStatus($job, 'Processed', 'info');
            case 'failed':
                return $this->writeStatus($job, 'Failed', 'error');
        }
    }

	/**
     * Format the status output for the queue worker.
     *
     * @param  \Yuga\Queue\Job  $job
     * @param  string  $status
     * @param  string  $type
     * @return void
     */
    protected function writeStatus(Job $job, $status, $type)
    {
        $date = Carbon::now()->format('Y-m-d H:i:s');

        $message = sprintf("<{$type}>[%s] %s</{$type}> %s", $date, str_pad("{$status}:", 11), $job->resolveName());

        $this->output->writeln($message);
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
            ['daemon', null, InputOption::VALUE_NONE,     'Run the worker in daemon mode'],
            ['delay',  null, InputOption::VALUE_OPTIONAL, 'Amount of time to delay failed jobs', 0],
            ['force',  null, InputOption::VALUE_NONE,     'Force the worker to run even in maintenance mode'],
            ['memory', null, InputOption::VALUE_OPTIONAL, 'The memory limit in megabytes', 128],
            ['sleep',  null, InputOption::VALUE_OPTIONAL, 'Number of seconds to sleep when no job is available', 3],
            ['tries',  null, InputOption::VALUE_OPTIONAL, 'Number of times to attempt a job before logging it failed', 0],
        ];
    }
}