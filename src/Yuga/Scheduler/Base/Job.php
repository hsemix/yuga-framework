<?php

namespace Yuga\Scheduler\Base;

/**
 * Jobs base abstract class.
 *
 * @author Alejandro Mostajo <http://about.me/amostajo>
 * @copyright 10quality <info@10quality.com>
 * @package Scheduler
 * @license MIT
 * @version 1.0.4
 */
abstract class Job
{
    /**
     * Task process linked to job.
     * @since 1.0.0
     * @var object
     */
    protected $task;

    /**
     * Method called to run job.
     * @since 1.0.0
     */
    public function execute()
    {
        // TODO
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
            case 'task':
                return $this->$property;
            case 'name':
                return get_class($this);
        }
        return null;
    }

    /**
     * Setter function.
     * @since 1.0.0
     *
     * @return mixed
     */
    public function __set($property, $value)
    {
        if ($property === 'task'
            && is_a($value, 'Yuga\Scheduler\Task')
        )   $this->$property = $value;
    }

    public function run()
    {
        return $this->execute();
    }
}