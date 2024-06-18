<?php 

namespace Yuga\Scheduler;

use Closure;
use Yuga\Application\Application;
use Yuga\Container\Container;
use Symfony\Component\Process\Process;
use Yuga\Exceptions\SchedulerException;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Class Job
 *
 * Represents a single task that should be scheduled
 * and run periodically.
 *
 * @property-read array $types
 * @property-read string $type
 * @property-read mixed $action
 * @property-read array $environments
 * @property-read string $name
 */
class Job
{
	use FrequenciesTrait;

	/**
	 * Supported action types.
	 *
	 * @var string[]
	 */
	protected $types = [
		'command',
		'shell',
		'closure',
		'event',
		'url',
	];

	protected $backgroundTasks = [
		'shell', 'command',
	];

	/**
     * The user the command should run as.
     *
     * @var string
     */
    public $user;

	/**
	 * The type of action.
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * The actual content that should be run.
	 *
	 * @var mixed
	 */
	protected $action;

	/**
	 * If not empty, lists the allowed environments
	 * this can run in.
	 *
	 * @var array
	 */
	protected $environments = [];

	/**
	 * The alias this task can be run by
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Console Application
	 */
	protected $console;
	
	/**
	 * Yuga Application
	 */
	protected $app;

	/**
     * Indicates if the command should run in background.
     *
     * @var bool
     */
    public $runInBackground = false;

	/**
     * The location that output should be sent to.
     *
     * @var string
     */
    public $output = '/dev/null';

    /**
     * Indicates whether output should be appended.
     *
     * @var bool
     */
    protected $shouldAppendOutput = false;

	/**
     * Indicates if the command should not overlap itself.
     *
     * @var bool
     */
    public $withoutOverlapping = false;

    /**
     * The amount of time the mutex should be valid.
     *
     * @var int
     */
    public $expiresAt = 1440;

	/**
	 * @param mixed  $action
	 * @param string $type
	 *
	 * @throws SchedulerException
	 */

	/**
     * The array of callbacks to be run before the job is started.
     *
     * @var array
     */
    protected $beforeCallbacks = [];

	protected $mutex;

    /**
     * The array of callbacks to be run after the job is finished.
     *
     * @var array
     */
    protected $afterCallbacks = [];
	public function __construct(String $type, $action)
	{
		if (!in_array($type, $this->types, true)) {
			throw SchedulerException::forInvalidTaskType( $type );
		}

		$this->type   = $type;
		$this->action = $action;

		$this->mutex = new CacheMutex();
	}

	public function setApplication($app)
	{
		$this->app = $app;
		return $this;
	}

	public function setConsole($console)
	{
		$this->console = $console;
		return $this;
	}

	/**
	 * Set the name to reference this task by
	 *
	 * @param string $name
	 *
	 * @return $this
	 */
	public function named( String $name ) : Job
	{
		$this->name = $name;

		return $this;
	}

	/**
	 * Returns the type.
	 *
	 * @return string
	 */
	public function getType(): String
	{
		return $this->type;
	}

	/**
	 * Returns the saved action.
	 *
	 * @return mixed
	 */
	public function getAction()
	{
		return $this->action;
	}

	/**
	 * Runs this Task's action.
	 *
	 * @throws SchedulerException
	 */
	public function run()
	{	
		$method = 'run' . ucfirst($this->type);

		if ($this->withoutOverlapping && !$this->mutex->create($this)) {
            return;
        }

		$this->callBeforeCallbacks($this->app);
		if (\in_array($this->type, $this->backgroundTasks)) {
			return $this->canRunInBackground($this->type);
		}
		
		if (!method_exists($this, $method)) {
			throw SchedulerException::forInvalidTaskType( $this->type );
		}
		
		$this->$method();
		$this->callAfterCallbacks($this->app);

		return $this;
	}

	protected function canRunInBackground($job)
	{	
		// $process = new Process($this->buildCommand(), path(), null, null, null);
		$process = Process::fromShellCommandline($this->buildCommand());

		if ($this->runInBackground) {
			$process->disableOutput();
		}

		$process->run();
		$this->callAfterCallbacks($this->app);
		return $this;
	}

	/**
	 * Determines whether this task should be run now
	 * according to its schedule and environment.
	 *
	 * @return boolean
	 */
	public function shouldRun( \Datetime $testTime = null ) : bool
	{
		$cron = new \Cron\CronExpression( $this->getExpression() );

		$testTime = ( $testTime ) ? $testTime : 'now';
		
		return $cron->isDue( $testTime );
	}

	/**
     * State that the command should run in background.
     *
     * @return $this
     */
    public function runInBackground()
    {
        $this->runInBackground = true;

        return $this;
    }

	/**
     * Call all of the "before" callbacks for the job.
     *
     * @param  \Yuga\Container\Container  $container
     * @return void
     */
    public function callBeforeCallbacks(Container $container)
    {
        foreach ($this->beforeCallbacks as $callback) {
            $container->call($callback);
        }
    }

	/**
     * Call all of the "after" callbacks for the job.
     *
     * @param  \Yuga\Container\Container  $container
     * @return void
     */
    public function callAfterCallbacks(Application $container)
    {
        foreach ($this->afterCallbacks as $callback) {
            $container->call($callback);
        }
    }

	/**
     * Do not allow the job to overlap each other.
     *
     * @param  int  $expiresAt
     * @return $this
     */
    public function withoutOverlapping($expiresAt = 1440)
    {
        $this->withoutOverlapping = true;

        $this->expiresAt = $expiresAt;

        return $this->then(function () {	
            $this->mutex->forget($this);
        });
    }

	/**
     * Register a callback to be called before the operation.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function before(Closure $callback)
    {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    /**
     * Register a callback to be called after the operation.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function after(Closure $callback)
    {
        return $this->then($callback);
    }

    /**
     * Register a callback to be called after the operation.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function then(Closure $callback)
    {
        $this->afterCallbacks[] = $callback;

        return $this;
    }

	/**
     * Build the command string.
     *
     * @return string
     */
    public function buildCommand()
    {
        $command = $this->compileCommand();

        if (!is_null($this->user) && ! windows_os()) {
            return 'sudo -u ' .$this->user .' -- sh -c \'' .$command .'\'';
        }

        return $command;
    }

	/**
     * Build a command string with mutex.
     *
     * @return string
     */
    protected function compileCommand()
    {
        $output = escapeshellarg($this->output);

        $redirect = $this->shouldAppendOutput ? ' >> ' : ' > ';

		if ($this->type == 'shell') {
			if (!$this->runInBackground) {
				return $this->getAction().$redirect.$output .' 2>&1';
			}
			return $this->getAction().$redirect.$output.' 2>&1 &';
		} else {
			if (!$this->runInBackground) {
				return 'php yuga '.$this->getAction().$redirect.$output .' 2>&1';
			}
	
			$phpBinary = escapeshellarg((new PhpExecutableFinder)->find(false));
	
			$yugaBinary = escapeshellarg('yuga');
	
			return $phpBinary .' ' . $yugaBinary . ' '.$this->getAction().$redirect.$output.' 2>&1 &';
		} 
    }

	/**
     * Get the mutex path for the scheduled command.
     *
     * @return string
     */
    public function mutexName()
    {
		return $this->buildName();
    }

	/**
     * Get the default output depending on the OS.
     *
     * @return string
     */
    protected function getDefaultOutput()
    {
        return windows_os() ? 'NUL' : '/dev/null';
    }


	/**
     * Set which user the command should run as.
     *
     * @param  string  $user
     * @return $this
     */
    public function user($user)
    {
        $this->user = $user;

        return $this;
    }


	/**
	 * Restricts this task to run within only
	 * specified environements.
	 *
	 * @param mixed ...$environments
	 *
	 * @return $this
	 */
	public function environments(...$environments)
	{
		$this->environments = $environments;

		return $this;
	}

	/**
	 * Checks if it runs within the specified environment.
	 *
	 * @param string $environment
	 *
	 * @return boolean
	 */
	protected function runsInEnvironment(string $environment): bool
	{
		// If nothing is specified then it should run
		if (empty($this->environments)) {
			return true;
		}

		return in_array( $environment, $this->environments, true );
	}

	/**
	 * Runs a framework Command.
	 *
	 * @return mixed Buffered output from the Command
	 * @throws \InvalidArgumentException
	 */
	protected function runCommand()
	{
		return exec(PHP_BINARY . " yuga {$this->getAction()}");
	}

	/**
	 * Executes a shell script.
	 *
	 * @return array Lines of output from exec
	 */
	protected function runShell(): Array
	{
		exec($this->getAction(), $output);

		return $output;
	}

	/**
	 * Calls a Closure.
	 *
	 * @return mixed The result of the closure
	 */
	protected function runClosure()
	{	
		$this->getAction()->__invoke();	
	}

	/**
	 * Triggers an Event.
	 *
	 * @return mixed Result of the trigger
	 */
	protected function runEvent()
	{
		return $this->app['events']->dispatch($this->getAction());
	}

	/**
	 * Queries a URL.
	 *
	 * @return mixed|string Body of the Response
	 */
	protected function runUrl()
	{
		
	}

	/**
     * Builds a unique name for the task.
     * Used when an existing name doesn't exist.
     *
     * @return string
     * @throws \ReflectionException
     */
    protected function buildName()
    {
        // Get a hash based on the action
        // Closures cannot be serialized so do it the hard way
        if ($this->getType() === 'closure') {
            $ref  = new \ReflectionFunction($this->getAction());
            $file = new \SplFileObject($ref->getFileName());
            $file->seek($ref->getStartLine() - 1);
            $content = '';

            while ($file->key() < $ref->getEndLine()) {
                $content .= $file->current();
                $file->next();
            }
            $actionString = json_encode([
                $content,
                $ref->getStaticVariables()
            ]);

        } else {
            $actionString = serialize( $this->getAction() );
        }

        // Get a hash based on the expression
        $expHash = $this->getExpression();

        return  $this->getType() . '_' . md5( $actionString . '_' . $expHash );
    }

	/**
	 * Magic getter
	 *
	 * @param string $key
	 *
	 * @return mixed
	 */
	public function __get( String $key )
	{
		if ($key === 'name' && empty($this->name)) {
            return $this->buildName();
        }

		if (property_exists($this, $key)) {
			return $this->{ $key };
		}
	}
}