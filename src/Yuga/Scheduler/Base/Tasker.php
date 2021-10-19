<?php

namespace Yuga\Scheduler\Base;

use Closure;
use stdClass;
use Exception;
use Yuga\Scheduler\Task;
use Yuga\Interfaces\Scheduler\Session\Session;

/**
 * Scheduler base abstract class.
 *
 * @author Alejandro Mostajo <http://about.me/amostajo>
 * @copyright 10quality <info@10quality.com>
 * @package Scheduler
 * @license MIT
 * @version 1.0.4
 */
abstract class Tasker
{
    /**
     * Path to where jobs are located.
     * @since 1.0.0
     * @var array
     */
    protected $jobsPath;

    /**
     * Path to where jobs are located.
     * @since 1.0.0
     * @var array
     */
    protected $session;

    /**
     * List of jobs to run.
     * @since 1.0.0
     * @var array
     */
    protected $jobs;

    /**
     * Event callables.
     * @since 1.0.4
     * @var array
     */
    protected $events = [];

    /**
     * Default constructor.
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->time = time();
        $this->jobs = [];
    }

    /**
     * Adds job to list.
     * @since 1.0.0
     *
     * @param string $name Job name.
     * @param object $task Clouse task creator.
     *
     * @return this for chaining
     */
    public function job($name, Closure $task)
    {
        $job = new $name();

        // Assign task
        $job->task = $task(new Task);

        // Add to list of jobs
        $this->jobs[] = $job;

        // Chaining
        return $this;    
    }

    /**
     * Starts tasker.
     * @since 1.0.0
     *
     * @return this for chaining
     */
    public function start()
    {
        if ($this->hasEvent('on_init'))
            call_user_func_array($this->events['on_init'], [microtime()]);
        if (!$this->session instanceof Session)
            throw new Exception('Session driver must implement "Yuga\Interfaces\Scheduler\Session\Session" interface.');
        if ($this->hasEvent('on_start'))
            call_user_func_array($this->events['on_start'], [microtime()]);
        // Check on first time execution
        if (!$this->session->has('last_exec_time'))
            $this->session->set('last_exec_time', 0);
        // Loop jobs
        for ($i = count($this->jobs) - 1; $i >= 0; --$i) {
            $this->executeJob($i);
        }
        // Scheduler finished.
        $this->session->set('last_exec_time', time());
        $this->session->save();
        if ($this->hasEvent('on_finish'))
            call_user_func_array($this->events['on_finish'], [microtime()]);
        // Chaining
        return $this;    
    }

    /**
     * Executes job at index passed by.
     * @since 1.0.0
     *
     * @param int $index Index.
     */
    private function executeJob($index)
    {
        if ($this->hasEvent('on_job_start'))
            call_user_func_array($this->events['on_job_start'], [$this->jobs[$index]->name, microtime()]);
        try {
            if ($this->onSchedule($this->jobs[$index])) {
                $this->jobs[$index]->execute();
                $this->log($this->jobs[$index]);
            }
        } catch (Exception $e) {
            $this->onException($this->jobs[$index], $e);
            $this->resetLog($this->jobs[$index]);
            if ($this->hasEvent('on_exception'))
                call_user_func_array($this->events['on_exception'], [$e]);
        }
        if ($this->hasEvent('on_job_finish'))
            call_user_func_array($this->events['on_job_finish'], [$this->jobs[$index]->name, microtime()]);
    }

    /**
     * Returns flag indicating if task is onSchedule and ready to be executed.
     * @since 1.0.0
     *
     * @param \Scheduler\Base\Job $job
     */
    private function onSchedule(Job &$job)
    {
        if (!$this->session->has('jobs')
            && !isset($this->session->get('jobs')->{$job->name})
        ) return true;

        switch ($job->task->interval) {
            case Task::MIN1:
                if ($this->lapsedTimeToMinutes($job) > 1)
                    return true;
                break;
            case Task::MIN5:
                if ($this->lapsedTimeToMinutes($job) > 5)
                    return true;
                break;
            case Task::MIN10:
                if ($this->lapsedTimeToMinutes($job) > 10)
                    return true;
                break;
            case Task::MIN30:
                if ($this->lapsedTimeToMinutes($job) > 30)
                    return true;
                break;
            case Task::MIN60:
                if ($this->lapsedTimeToMinutes($job) > 60)
                    return true;
                break;
            case Task::MIN720:
                if ($this->lapsedTimeToMinutes($job) > 720)
                    return true;
                break;
            case Task::DAILY:
                if ($this->timeToDay($job) != date('Ymd'))
                    return true;
                break;
            case Task::MONTHLY:
                if ($this->timeToMonth($job) != date('Ym'))
                    return true;
                break;
            case Task::WEEKLY:
                if ($this->timeToWeek($job) != date('YW'))
                    return true;
                break;
            case Task::CUSTOM:
                if ($job->task->minutes !== null && $this->lapsedTimeToMinutes($job) > $job->task->minutes)
                    return true;
                break;
            case Task::EVERY2DAYS:
                $day = date('Ymd');
                if ($this->timeToDay($job) != $day && $this->timeToDay($job, '+1 day') != $day)
                    return true;
                break;
            case Task::EVERY3DAYS:
                $day = date('Ymd');
                if ($this->timeToDay($job) != $day
                    && $this->timeToDay($job, '+1 day') != $day
                    && $this->timeToDay($job, '+2 day') != $day
                )
                    return true;
                break;
            case Task::NOW:
                    return true;
        }
        return false;
    }

    /**
     * Buils session log structure.
     * @since 1.0.4
     *
     * @param \Scheduler\Base\Job $job
     */
    private function buildSessionLog(Job &$job)
    {
        if (!$this->session->has('jobs'))
            $this->session->set('jobs', new stdClass);

        if (!isset($this->session->get('jobs')->{$job->name}))
            $this->session->get('jobs')->{$job->name} = new stdClass;
    }

    /**
     * Logs executed job.
     * @since 1.0.0
     *
     * @param \Scheduler\Base\Job $job
     */
    private function log(Job &$job)
    {
        $this->buildSessionLog($job);
        $this->session->get('jobs')->{$job->name}->time = time();
    }

    /**
     * Resets log, forcing it to run in the next run.
     * @since 1.0.4
     *
     * @param \Scheduler\Base\Job $job
     */
    private function resetLog(Job &$job)
    {
        if (!$job->task->canReset)
            return;
        $this->buildSessionLog($job);
        $this->session->get('jobs')->{$job->name}->time = 0;
    }

    /**
     * Handles exception.
     * @since 1.0.4
     *
     * @param \Scheduler\Base\Job $job
     * @param Exception           $e
     */
    private function onException(Job &$job, Exception &$e)
    {
        if ($job->task->onExceptionCallable)
            call_user_func_array($job->task->onExceptionCallable, [$e]);
    }

    /**
     * Returns lapsed time to minutes.
     * @since 1.0.0
     *
     * @param \Scheduler\Base\Job $job
     *
     * @return float
     */
    private function lapsedTimeToMinutes(Job &$job)
    {
        return ($this->time - $this->session->get('jobs')->{$job->name}->time) / 60;
    }

    /**
     * Returns last executed to day.
     * @since 1.0.0
     * 
     * @param \Scheduler\Base\Job &$job
     * @param string              $time Time modifications [see strtotime()].
     *
     * @return string
     */
    private function timeToDay(Job &$job, $time = null)
    {
        return date('Ymd', $time === null
            ? $this->session->get('jobs')->{$job->name}->time
            : strtotime($time, $this->session->get('jobs')->{$job->name}->time)
        );
    }

    /**
     * Returns last executed to day.
     * @since 1.0.0
     * 
     * @param \Scheduler\Base\Job &$job
     * @param string              $time Time modifications [see strtotime()].
     *
     * @return string
     */
    private function timeToMonth(Job &$job, $time = null)
    {
        return date('Ym', $time === null
            ? $this->session->get('jobs')->{$job->name}->time
            : strtotime($time, $this->session->get('jobs')->{$job->name}->time)
        );
    }

    /**
     * Returns last executed to day.
     * @since 1.0.0
     * 
     * @param \Scheduler\Base\Job &$job
     * @param string              $time Time modifications [see strtotime()].
     *
     * @return string
     */
    private function timeToWeek(Job &$job, $time = null)
    {
        return date('YW', $time === null
            ? $this->session->get('jobs')->{$job->name}->time
            : strtotime($time, $this->session->get('jobs')->{$job->name}->time)
        );
    }

    /**
     * Returns flag indicating if there is an event callable available or not.
     * @since 1.0.4
     * 
     * @param string $event
     * 
     * @return bool
     */
    private function hasEvent($event)
    {
        return array_key_exists($event, $this->events) && is_callable($this->events[$event]);
    }
}