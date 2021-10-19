<?php

namespace Yuga\Scheduler\Session;

use JSONArray;
use Yuga\Interfaces\Scheduler\Session\Session;

/**
 * File based server session.
 * Session driver.
 *
 * @author Alejandro Mostajo <http://about.me/amostajo>
 * @copyright 10quality <info@10quality.com>
 * @package Scheduler
 * @license MIT
 * @version 1.0.3
 */
class File implements Session
{
    protected $data = [];

    public static function load($options)
    {
        return new self(is_array($options) ? $options : []);
    }

    public function __construct($data = [])
    {
        $this->data = $data;
    }

    public function save()
    {
        // TODO no session to saved
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function &get($key)
    {
        $value = $this->has($key) ? $this->data[$key] : null;
        return $value;
    }

    public function has($key)
    {
        return array_key_exists($key, $this->data);
    }
}