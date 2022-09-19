<?php 

namespace Yuga\Scheduler;

use Config\Database;
use Yuga\Carbon\Carbon;
use Yuga\Console\Command;
use Yuga\Support\FileSystem;
use Yuga\Console\Application;

/**
 * Class TaskRunner
 *
 * @package CodeIgniter\Tasks
 */
class JobRunner
{
	/**
	 * @var Scheduler
	 */
	protected $scheduler;

	/**
	 * @var string
	 */
	protected $testTime = null;

	/**
     * Stores aliases of tasks to run
     * If empty, All tasks will be executed as per their schedule
     *
     * @var array
     */
    protected $only = [];

	/**
	 * Stores execution logs for each
	 * task that was ran
	 *
	 * @var array
	 */
	protected $performanceLogs = [];

	/**
	 * Current Command
	 */
	public $command;

	public function __construct()
	{
		$this->scheduler = app('scheduler');
	}

	/**
	 * The main entry point to run tasks within the system.
	 * Also handles collecting output and sending out
	 * notifications as necessary.
	 */
	public function run()
	{
		$tasks = $this->scheduler->getTasks();

		if (!count($tasks)) {
			return;
		}

		foreach ($tasks as $task) {
			// If specific tasks were chosen then skip executing remaining tasks
            if (!empty($this->only) && !in_array($task->name, $this->only, true)) {
                continue;
            }

			if (!$task->shouldRun($this->testTime) && empty($this->only)) {
				continue;
			}

			$error  = null;
			$start  = Carbon::now();
			$output = null;

			$this->cliWrite( 'Processing: ' . ( $task->name ?: 'Task' ), 'comment' );

			try {
				$output = $task->run();
				
				if (!$output) {
					$output = \ob_get_contents();
				}

				$this->cliWrite( 'Executed: ' . ( $task->name ?: 'Task' ), 'info' );
			} catch(\Throwable $e) {
				$this->cliWrite( 'Failed: ' . ($task->name ?: 'Task'), 'error' );
				//log to file -- to do
				$loggerFile = storage('logs/log-' . date('Y-m-d') . '.log');

				FileSystem::createDir(storage('logs'));
				
				FileSystem::write($loggerFile, $e . "\n", FILE_APPEND);
				$error = $e;
			} finally {
				$end = Carbon::now();

				$jobLog = new JobLog(['task' => $task, 'output' => $output, 'runStart' => $start, 'runEnd' => $end, 'error' => $error, 'testTime' => $this->testTime]);
				
				$this->performanceLogs[] = [
					'name' => ( $task->name ) ? $task->name : null,
					'type' => $task->getType(),
					'action' => (\is_object($task->getAction())) ? \json_encode($task->getAction()) : $task->getAction(),
					// 'environment' => \json_encode( $task->environments ),
					'output' => $output,
					'error' => $error,
					'start_at' => \strval( $start ),
					'end_at' => \strval( $end ),
					'duration' => $jobLog->duration(),
					'test_time' => ($this->testTime) ? $this->testTime->format( 'Y-m-d H:i:s' ) : null
				];

				$this->storePerformanceLogs();
			}
		}
	}

	/**
     * Specify tasks to run
     *
     * @param array $tasks
     *
     * @return TaskRunner
     */
    public function only(Array $tasks = []): JobRunner
    {
        $this->only = $tasks;

        return $this;
    }

	/**
	 * Sets a time that will be used.
	 * Allows setting a specific time to test against.
	 * Must be in a DateTime-compatible format.
	 *
	 * @param string $time
	 *
	 * @return $this
	 */
	public function withTestTime(String $time): JobRunner
	{
		$this->testTime = new \DateTime($time);

		return $this;
	}


	/**
	 * Performance log information is stored
	 * at /writable/tasks/tasks_yyyy_mm_dd.json
	 */
	protected function storePerformanceLogs()
	{
		$config = config('scheduler');

		if (empty( $this->performanceLogs)) {
			return;
		}

		if ($config['logSavingMethod'] == 'database') {

			foreach ($this->performanceLogs as $log)
				\Yuga\Scheduler\Models\SchedulerJobLog::create($log);
			
		} else {

			// Ensure we have someplace to store the log
			if (file_exists($config['FilePath'] . '/' . $config['FileName'])) {
				if (!is_dir($config['FilePath'])) { 
					mkdir( $config['FilePath']); 
				}
			}

			$fileName = 'jobs_' . date('Y-m-d--H-i-s') . '.json';

			// write the file with json content
			file_put_contents(
				$config['FilePath'] . '/' . $fileName,
				json_encode(
					$this->performanceLogs, 
					JSON_PRETTY_PRINT
				)
			);
		}
	}

	/**
     * Write a line to command line interface
     *
     * @param string      $text
     * @param string|null $foreground
     */
    protected function cliWrite( String $text, String $foreground = null )
    {
        // Skip writing to cli in tests

        if (!$this->scheduler->app->runningInConsole()) {
            return ;
        }

        $this->command->{$foreground}("[" . date("Y-m-d H:i:s") . "] " . $text);
    }
}