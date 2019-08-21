<?php

namespace Yuga\Events\Dispatcher;

use Yuga\Events\Exceptions\EventException;
use Yuga\Shared\Controller as SharedController;

class Dispatcher
{
    use SharedController;
    /**
     * @var string event name
     */
    protected $name;
    
    /**
     * @var array the event parameters
     */
    protected $params = [];

    /**
     * @var array list of attributes
     */
    private $attributes = [];

    /**
     * The event instance is to be stored in this variable
     *
     * @var \Yuga\Events\Dispatcher\Dispatcher
     */
    protected static $event;

    public function __construct($name = null, $params = null)
    {   
        if ($name) {
            $this->setName($name);
        }
        
        if ($params) {
            $this->setParams($params);
        }

        if (method_exists($this, 'init')) {
            $this->init();
        }
        if (!static::$event) {
            static::$event = $this;
        }
    }

    /**
     * Get event name
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * Overwrites parameters
     *
     * @param  array|object $params
     *
     * @return void
     * @throws EventException
     */
    public function setParams($params)
    {
        if (!is_array($params)) {
            throw new EventException('Event parameters must be an array; received `' . gettype($params) . '`');
        }
        $this->params = $params;
    }

    /**
     * Get all parameters
     *
     * @return array|object
     */
    public function getParams()
    {
        return $this->params;
    }
    /**
     * Get an individual parameter
     *
     * If the parameter does not exist, the $default value will be returned.
     *
     * @param  string|int $name
     * @param  mixed      $default
     *
     * @return mixed
     */
    public function getParam($name, $default = null)
    {
        if (is_array($this->params)) {
            // Check in params that are arrays or implement array access
            return $this->params[$name] ?? $default;
        } else {
            // Wrong type, return default value
            return $default;
        }
    }
    /**
     * Set the event name
     *
     * @param  string $name
     *
     * @return void
     */
    public function setName($name)
    {
        $this->name = (string)$name;
        return $this;
    }
    /**
     * Set an individual parameter to a value
     *
     * @param  string|int $name
     * @param  mixed      $value
     *
     * @return void
     */
    public function setParam($name, $value)
    {
        if (is_array($this->params)) {
            // Arrays or objects implementing array access
            $this->params[$name] = $value;
        }
    }

    public function __get($key)
    {
        return $this->getAttribute($key);
    }
	/**
	* Set a variable and make an object point to it
	*/
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
        
        return $this;
    }

    /**
     * Get an attribute from the event.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($name)
    {
        return $this->attributes[$name];
    }

    public function setAttributes(array $attributes = [])
    {
        foreach ($attributes as $key => $value)
            $this->attributes[$key] = $value;
        
        return $this;
    }

    /**
     * Return a static instance of the event instance throught the entire application
     * 
     * @param null
     * 
     * @return \Yuga\Events\Dispatcher\Dispatcher
     */
    public static function getInstance()
    {
        return static::$event;
    }

    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->getAttribute('dispatcher'), $method], $parameters);
    }
}