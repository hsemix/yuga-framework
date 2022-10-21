<?php

namespace Yuga\Scheduler;

use Yuga\Exceptions\SchedulerException;

/**
 * Class Job.
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
     * The alias this task can be run by.
     *
     * @var string
     */
    protected $name;

    /**
     * Console Application.
     */
    protected $console;

    /**
     * Yuga Application.
     */
    protected $app;

    /**
     * Indicates if the command should run in background.
     *
     * @var bool
     */
    public $runInBackground = false;

    /**
     * @param mixed  $action
     * @param string $type
     *
     * @throws SchedulerException
     */
    public function __construct(string $type, $action)
    {
        if (!in_array($type, $this->types, true)) {
            throw SchedulerException::forInvalidTaskType($type);
        }

        $this->type = $type;
        $this->action = $action;
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
     * Set the name to reference this task by.
     *
     * @param string $name
     *
     * @return $this
     */
    public function named(string $name): Job
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Returns the type.
     *
     * @return string
     */
    public function getType(): string
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
        $mutex = new \SyncMutex('UniqueName');

        echo 'kdkdkdd';
        // print_r($mutex);
        exit();
        // print_r($this->type);
        // die();
        $method = 'run'.ucfirst($this->type);
        if (!method_exists($this, $method)) {
            throw SchedulerException::forInvalidTaskType($this->type);
        }

        return $this->$method();
    }

    /**
     * Determines whether this task should be run now
     * according to its schedule and environment.
     *
     * @return bool
     */
    public function shouldRun(\DateTime $testTime = null): bool
    {
        $cron = \Cron\CronExpression::factory($this->getExpression());

        $testTime = ($testTime) ? $testTime : 'now';

        return $cron->isDue($testTime);
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
     * @return bool
     */
    protected function runsInEnvironment(string $environment): bool
    {
        // If nothing is specified then it should run
        if (empty($this->environments)) {
            return true;
        }

        return in_array($environment, $this->environments, true);
    }

    /**
     * Runs a framework Command.
     *
     * @throws \InvalidArgumentException
     *
     * @return mixed Buffered output from the Command
     */
    protected function runCommand()
    {
        return exec(PHP_BINARY." yuga {$this->getAction()}");
    }

    /**
     * Executes a shell script.
     *
     * @return array Lines of output from exec
     */
    protected function runShell(): array
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

        // $sem = sem_get(1234, 1);
        // if (sem_acquire($sem)) {
        return $this->getAction()->__invoke();
        // }
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
     * @throws \ReflectionException
     *
     * @return string
     */
    protected function buildName()
    {
        // Get a hash based on the action
        // Closures cannot be serialized so do it the hard way
        if ($this->getType() === 'closure') {
            $ref = new \ReflectionFunction($this->getAction());
            $file = new \SplFileObject($ref->getFileName());
            $file->seek($ref->getStartLine() - 1);
            $content = '';

            while ($file->key() < $ref->getEndLine()) {
                $content .= $file->current();
                $file->next();
            }
            $actionString = json_encode([
                $content,
                $ref->getStaticVariables(),
            ]);
        } else {
            $actionString = serialize($this->getAction());
        }

        // Get a hash based on the expression
        $expHash = $this->getExpression();

        return  $this->getType().'_'.md5($actionString.'_'.$expHash);
    }

    /**
     * Magic getter.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get(string $key)
    {
        if ($key === 'name' && empty($this->name)) {
            return $this->buildName();
        }

        if (property_exists($this, $key)) {
            return $this->{ $key };
        }
    }
}
