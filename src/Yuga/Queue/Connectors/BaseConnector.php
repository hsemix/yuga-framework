<?php 

namespace Yuga\Queue\Connectors;

use Yuga\Carbon\Carbon;
use Yuga\Queue\CanFailInterface;

/**
 * Base Queue handler.
 */
abstract class BaseConnector
{
	/**
     * Run every 5 seconds by default
     */
    const DEFAULT_INTERVAL = 5;

	/**
     * Run every X seconds
     *
     * @var int
     */
    protected $interval = self::DEFAULT_INTERVAL;

	/**
     *
     * @var float
     */
    protected $startTime;

    /**
     *
     * @var float
     */
    protected $passedTime;
	/**
	 * @var string
	 */
	protected $defaultQueue;

	/**
	 * when the message will be available for
	 * execution
	 *
	 * @var DateTime
	 */
	protected $available_at;

	/**
	 * constructor.
	 *
	 * @param array         $groupConfig
	 * @param \Config\Queue $config
	 */
	public function __construct($groupConfig, $config)
	{   
		$this->defaultQueue = $config['default'];

		$this->available_at = new Carbon;
	}

	/**
	 * send message to queueing system.
	 *
	 * @param array  $data
	 * @param string $queue
	 */
	abstract public function send($data, string $queue = '');

	/**
	 * Fetch message from queueing system.
	 * When there are no message, this method will return (won't wait).
	 *
	 * @param  callable $callback
	 * @param  string   $queue
	 * @return boolean  whether callback is done or not.
	 */
	abstract public function fetch(callable $callback, string $queue = '', $shouldStop = false) : bool;

	/**
	 * Receive message from queueing system.
	 * When there are no message, this method will wait.
	 *
	 * @param  callable $callback
	 * @param  string   $queue
	 * @return boolean  whether callback is done or not.
	 */
	abstract public function receive(callable $callback, string $queue = '') : bool;

	abstract public function reset();

	/**
	 * Set the delay in minutes
	 *
	 * @param  integer $min
	 * @return $this
	 */
	public function delay($min)
	{
		$this->available_at = (new Time)->modify('+' . $min . ' minutes');

		return $this;
	}

	/**
	 * run a command from the queue
	 *
	 * @param string $command the command to run
	 */
	public function command(string $command)
	{
		$data = [
			'command' => $command,
		];

		return $this->send($data);
	}

	/**
	 * run an anonymous function from the queue.
	 *
	 * @param callable $closure function to run
	 *
	 * TODO: this currently doesn't work with database
	 * as you can't serialize a closure. May need
	 * to implement something like laravel does to get
	 * around this.
	 */
	public function closure(callable $closure)
	{
		$data = [
			'closure' => $closure,
		];

		return $this->send($data);
	}

	/**
	 * run a job from the queue
	 *
	 * @param string $job  the job to run
	 * @param mixed  $data data for the job
	 */
	public function job(string $job, $data = [])
	{
		$data = [
			'job'  => $job,
			'data' => $data,
		];

		return $this->send($data);
	}

	/**
	 * run a job from the queue
	 *
	 * @param string $job  the job to run
	 * @param array  $data data for the job
	 */
	protected function fireOnFailure(\Throwable $exception, $data, $queueJob)
	{
		$job = unserialize($data['data']['job']);
		if ($job instanceof CanFailInterface) {
			$job->onFailure($queueJob, $exception->getMessage());
		}
		event('queue:failure', compact('job', 'queueJob', 'exception'));
	}

	/**
	 * run a job from the queue
	 *
	 * @param string $job  the job to run
	 * @param array  $data data for the job
	 */
	protected function fireOnSuccess($data, $queueJob)
	{
		$job = unserialize($data['data']['job']);
		event('queue:successful', compact('job', 'queueJob'));
	}

	/**
     * Create a payload string from the given job and data.
     *
     * @param  string  $job
     * @param  mixed   $data
     * @param  string  $queue
     * @return string
     */
    public function createPayload($job, $data = [], $queue = null)
    {
        if (is_object($job)) {
            $payload = $this->createObjectPayload($job, $data);
        	return json_encode($payload);
		}
    }

	/**
     * Create a payload string for the given Closure job.
     *
     * @param  object  $job
     * @param  mixed   $data
     * @return string
     */
    protected function createObjectPayload($job, $data = []): bool
    {
        $jobName = get_class($job);

        $job = serialize(clone $job);

        $this->send([
            'job'  => 'Yuga\Queue\CallQueuedHandler@call',
            'data' => compact('jobName', 'job'),
		]);

		return true;
    }

	/**
     *
     * @return int
     */
    public function getInterval()
    {
        return $this->interval;
    }

	/**
     *
     * @param int $interval
     *
     * @return static
     */
    public function setInterval($interval)
    {
        $this->interval = $interval;

        return $this;
    }

	/**
     * Start timing
     */
    public function startTime()
    {
        $this->startTime = microtime(true);
    }

    /**
     * Get passed time
     *
     * @return float
     */
    public function getPassedTime()
    {
        return abs(microtime(true) - $this->startTime);
    }

    /**
     * Sleep
     *
     * @return null
     */
    public function sleep()
    {
        // Time ... enough
        if ($this->getPassedTime() <= $this->interval) {

            $remainder = ($this->interval) - $this->getPassedTime();
            usleep($remainder * 1000000);
        } // Task took more than the interval, don't sleep
    }
}
