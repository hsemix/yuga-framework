<?php

namespace Yuga\Scheduler;

/**
 * Task class.
 *
 * @author Alejandro Mostajo <http://about.me/amostajo>
 * @copyright 10quality <info@10quality.com>
 * @package Scheduler
 * @license MIT
 * @version 1.0.4
 */
class Task
{
    /**
     * Daily task constant.
     * @since 1.0.0
     * @var int
     */
    const DAILY = 0;

    /**
     * Every 1 minute task constant.
     * @since 1.0.0
     * @var int
     */
    const MIN1 = 1;

    /**
     * Every 5 minutes task constant.
     * @since 1.0.0
     * @var int
     */
    const MIN5 = 2;

    /**
     * Every 10 minutes task constant.
     * @since 1.0.0
     * @var int
     */
    const MIN10 = 3;

    /**
     * Every 30 minutes task constant.
     * @since 1.0.0
     * @var int
     */
    const MIN30 = 4;

    /**
     * Every 60 minutes / 1 hour task constant.
     * @since 1.0.0
     * @var int
     */
    const MIN60 = 5;

    /**
     * Every 12 hours task constant.
     * @since 1.0.0
     * @var int
     */
    const MIN720 = 6;

    /**
     * Once every month task constant.
     * @since 1.0.0
     * @var int
     */
    const MONTHLY = 7;

    /**
     * Once every week task constant.
     * @since 1.0.0
     * @var int
     */
    const WEEKLY = 8;

    /**
     * Once every 2 days.
     * @since 1.0.3
     * @var int
     */
    const EVERY2DAYS = 9;

    /**
     * Once every 3 days.
     * @since 1.0.3
     * @var int
     */
    const EVERY3DAYS = 10;

    /**
     * Now task constant.
     * @since 1.0.0
     * @var int
     */
    const NOW = -1;

    /**
     * Custom minute interval.
     * @since 1.0.3
     * @var int
     */
    const CUSTOM = -2;

    /**
     * Task process linked to job.
     * @since 1.0.0
     * @var object
     */
    protected $interval;

    /**
     * Custom time value if custom is selected.
     * @since 1.0.0
     * @var object
     */
    protected $minutes = null;

    /**
     * Flag that indicates if task can be reset if exception occurs.
     * @since 1.0.4
     * @var bool
     */
    protected $canReset = false;

    /**
     * Callback to use when an exception occurs.
     * @since 1.0.4
     * @var callable
     */
    protected $onExceptionCallable = null;

    /**
     * Sets task to a daily interval.
     * @since 1.0.0
     *
     * @return this
     */
    public function daily()
    {
        $this->interval = self::DAILY;
        return $this;
    }

    /**
     * Sets task to a weekly interval.
     * @since 1.0.0
     *
     * @return this
     */
    public function weekly()
    {
        $this->interval = self::WEEKLY;
        return $this;
    }

    /**
     * Sets task to a monthly interval.
     * @since 1.0.0
     *
     * @return this
     */
    public function monthly()
    {
        $this->interval = self::MONTHLY;
        return $this;
    }

    /**
     * Sets task to a minute interval.
     * @since 1.0.0
     *
     * @return this
     */
    public function everyMinute()
    {
        $this->interval = self::MIN1;
        return $this;
    }

    /**
     * Sets task to a 5 minutes interval.
     * @since 1.0.0
     *
     * @return this
     */
    public function everyFiveMinutes()
    {
        $this->interval = self::MIN5;
        return $this;
    }

    /**
     * Sets task to a 10 minutes interval.
     * @since 1.0.0
     *
     * @return this
     */
    public function everyTenMinutes()
    {
        $this->interval = self::MIN10;
        return $this;
    }

    /**
     * Sets task to a 30 minutes interval.
     * @since 1.0.0
     *
     * @return this
     */
    public function everyHalfHour()
    {
        $this->interval = self::MIN30;
        return $this;
    }

    /**
     * Sets task to a 60 minutes interval.
     * @since 1.0.0
     *
     * @return this
     */
    public function everyHour()
    {
        $this->interval = self::MIN60;
        return $this;
    }

    /**
     * Sets task to a 720 minutes interval.
     * @since 1.0.0
     *
     * @return this
     */
    public function everyTwelveHours()
    {
        $this->interval = self::MIN720;
        return $this;
    }

    /**
     * Sets task to a now/constant interval.
     * @since 1.0.0
     *
     * @return this
     */
    public function now()
    {
        $this->interval = self::NOW;
        return $this;
    }

    /**
     * Sets task to a 2 days interval.
     * @since 1.0.3
     *
     * @return this
     */
    public function everyTwoDays()
    {
        $this->interval = self::EVERY2DAYS;
        return $this;
    }

    /**
     * Sets task to a 3 days interval.
     * @since 1.0.3
     *
     * @return this
     */
    public function everyThreeDays()
    {
        $this->interval = self::EVERY3DAYS;
        return $this;
    }

    /**
     * Sets task twice weekly interval.
     * @since 1.0.3
     * 
     * @param int $minutes Custom minutes interval value.
     *
     * @return this
     */
    public function custom($minutes)
    {
        $this->interval = self::CUSTOM;
        $this->minutes = $minutes;
        if (empty($this->minutes) || !is_integer($this->minutes))
            $this->minutes = null;
        return $this;
    }

    /**
     * Sets reset flag.
     * @since 1.0.4
     * 
     * @param bool $flag Reset flag.
     *
     * @return this
     */
    public function canReset($flag = true)
    {
        $this->canReset = $flag ? true : false;
        return $this;
    }

    /**
     * Sets exception callable.
     * @since 1.0.4
     * 
     * @param callable $callable
     *
     * @return this
     */
    public function onException($callable)
    {
        $this->onExceptionCallable = $callable;
        return $this;
    }

    /**
     * Getter function.
     * @since 1.0.0
     *
     * @return mixed
     */
    public function __get($property)
    {
        switch ($property) {
            case 'interval':
            case 'minutes':
            case 'canReset':
            case 'onExceptionCallable':
                return $this->$property;
        }
        return null;
    }
}