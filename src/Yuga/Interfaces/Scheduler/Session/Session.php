<?php

namespace Yuga\Interfaces\Scheduler\Session;
/**
 * Session contract | interface.
 *
 * @author Alejandro Mostajo <http://about.me/amostajo>
 * @copyright 10quality <info@10quality.com>
 * @package Scheduler
 * @license MIT
 * @version 1.0.0
 */
interface Session
{
    /**
     * Loads session.
     * @since 1.0.0
     *
     * @param mixed $options Options to load session.
     *
     * @return object Session.
     */
    public static function load($options);

    /**
     * Saves session.
     * @since 1.0.0
     */
    public function save();

    /**
     * Sets a key and a value into session.
     * Server session.
     * @since 1.0.0
     *
     * @param string $key   Key.
     * @param mixed  $value Value;
     */
    public function set($key, $value);

    /**
     * Returns a session value by reference based on a given key.
     * @since 1.0.0
     *
     * @return mixed     
     */
    public function &get($key);

    /**
     * Returns flag indicating if key exists in session.
     * @since 1.0.0
     *
     * @return bool     
     */
    public function has($key);
}