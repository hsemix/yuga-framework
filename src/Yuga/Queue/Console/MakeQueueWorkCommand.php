<?php

namespace Yuga\Queue\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Yuga\Console\CLI;
use Yuga\Console\Command;
use Yuga\Database\Query\Exceptions\DatabaseQueryException;

/**
 * Generates a skeleton migration file.
 */
class MakeQueueWorkCommand extends Command
{
    /**
     * The Command's Name.
     *
     * @var string
     */
    protected $name = 'queue:work';

    /**
     * The Command's Description.
     *
     * @var string
     */
    protected $description = 'Works the queue.';

    protected $startTime;

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function handle()
    {
        $cli = new CLI();
        $queue = $this->option('queue') ?? config('queue')['default'];
        $cli->writeLine('Working Queue: '.$queue, 'yellow');

        $response = true;
        $jobsProcessed = 0;
        $startTime = time();
        $this->yuga['queue']->startTime();
        if ($queue != 'wait') {
            $maxTries = 5;
            do {
                retry:
                try {
                    // $this->stopIfNecessary($startTime, $jobsProcessed);

                    $response = $this->yuga['queue']->fetch([$this, 'fire'], $queue);

                    // echo $response;
                    $jobsProcessed++;

                    // After working, sleep
                    $this->yuga['queue']->sleep();
                } catch (DatabaseQueryException $e) {
                    usleep(5 * 1000000);
                    $response = false;
                } catch (\Exception $e) {
                    $cli->writeLine('Failed', 'red');
                    $cli->writeLine("Exception: {$e->getCode()} - {$e->getMessage()}\nfile: {$e->getFile()}:{$e->getLine()}");
                    usleep(5 * 1000000);
                } finally {
                    usleep(5 * 1000000);
                    // usleep($this->yuga['queue']->getTimeout() * 1000000);
                }
            } while ($response === true);
        }

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
        $cli = new CLI();

        // $data
        if ($data['job'] == 'Yuga\Queue\CallQueuedHandler@call') {
            $job = unserialize($data['data']['job']);
            $cli->writeLine('Running Job #'.$data['data']['jobName'], 'yellow');
            $app = app()->call([$job, 'run']);
            $cli->writeLine();
            $cli->writeLine('Finished Job #'.$data['data']['jobName'], 'green');
        } else {
            $cli->writeLine('Failed to run Job', 'red');
        }
    }

    /**
     * Determine if we should stop the worker.
     *
     * @param int $startTime
     * @param int $jobsProcessed
     */
    protected function stopIfNecessary($startTime, $jobsProcessed)
    {
        $cli = new CLI();
        $shouldQuit = false;

        $maxTime = ini_get('max_execution_time') - 5; //max execution time minus a bit of a buffer (5 sec).

        $maxMemory = ($this->getMemoryLimit() / 1024 / 1024) - 10; //max memory with a buffer (10MB);
        $memoryUsage = memory_get_usage(true) / 1024 / 1024;

        $maxBatch = config('queue')['settings']['maxWorkerBatch'];

        //max time limit.
        if ($maxTime > 0 && time() - $startTime > $maxTime) {
            $shouldQuit = true;
            $reason = 'Time Limit Reached';
        }
        //max memory
        elseif ($maxMemory > 0 && $memoryUsage > $maxMemory) {
            $shouldQuit = true;
            $reason = 'Memory Limit Reached';
        } elseif ($maxBatch > 0 && $jobsProcessed >= $maxBatch) {
            $shouldQuit = true;
            $reason = 'Maximum Batch Size Reached';
        }

        if (isset($reason)) {
            $cli->writeLine('Exiting Worker: '.$reason, 'yellow');
        }

        return $shouldQuit;
    }

    /**
     * calculate the memory limit.
     *
     * @return int memory limit in bytes.
     */
    protected function getMemoryLimit()
    {
        $memory_limit = ini_get('memory_limit');

        //if there is no memory limit just set it to 2GB
        if ($memory_limit = -1) {
            return 2 * 1024 * 1024 * 1024;
        }

        preg_match('/^(\d+)(.)$/', $memory_limit, $matches);

        if (!isset($matches[2])) {
            throw new \Exception('Unknown Memory Limit');
        }

        switch ($matches[2]) {
            case 'G':
                $memoryLimit = $matches[1] * 1024 * 1024 * 1024;
                break;
            case 'M':
                $memoryLimit = $matches[1] * 1024 * 1024;
                break;
            case 'K':
                $memoryLimit = $matches[1] * 1024;
                break;
            default:
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
