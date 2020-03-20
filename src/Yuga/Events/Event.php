<?php

namespace Yuga\Events;

use Closure;
use Yuga\EventHandlers\HandlerInterface;
use Yuga\Events\Exceptions\EventException;
use Yuga\Events\Dispatcher\Dispatcher;
use Yuga\Interfaces\Events\Dispatcher as IDispatcher;

class Event implements IDispatcher
{
    /**
     * @var array list of attributes
     */
    private $attributes = [];

    /**
     * @var array list of listeners
     */
    protected $listeners = [];

    /**
     * @var string name of the default events
     */
    protected $name = 'yuga.auto.events';

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

    /**
     * Attach callback to event
     *
     * @param  string   $eventName
     * @param  callable $callback|null
     * @param  int  $priority
     *
     * @return void
     */
    public function attach($eventName, $callback = null, $priority = 1)
    {
        $args = func_get_args();
        if ($eventName instanceof Dispatcher) {
            $eventName = $eventName->getName();
        }
        if (count($args) == 1) {
            $this->triggerObjectHandlers($eventName);
        } else {
            if (!isset($this->listeners[$eventName])) {
                $this->listeners[$eventName] = [];
            }
            if (!isset($this->listeners[$eventName][$priority])) {
                $this->listeners[$eventName][$priority] = [];
            }
            $this->listeners[$eventName][$priority][] = $callback;
        }
        return $this;
    }
    /**
     * Some times the name provided in the attach method might be an instance of the HandlerInterface
     * When that happens, Make sure the $event has the handle method
     * 
     * @param array|string $handlers
     * 
     * @return void
     */
    protected function triggerObjectHandlers($handlers)
    {
        if (is_array($handlers)) {
            foreach ($handlers as $handler) {
                if (!method_exists($handler, 'handle')) {
                    throw new EventException(get_class($handler).' must implement the `handle` method');
                }
                $this->listeners[$this->name][1][] = [$handler, 'handle'];
            }
            return;
        }
        if (!method_exists($handlers, 'handle')) {
            throw new EventException(get_class($handlers).' must implement the `handle` method');
        }

        $this->listeners[$this->name][1][] = [$handlers, 'handle'];
    }

    /**
     * Dispatch event
     *
     * @param  string|Event  $event
     * @param  array  $parameters
     *
     * @return Event $event
     */
    public function dispatch($event = null, $parameters = null, $callback = null)
    {
        if (count(func_get_args()) == 2) {
            extract($this->getParameters($parameters));
        } else {
            $params = $parameters;
        }
        if (!$event)
            $event = $this->name;
        if (!$event instanceof Dispatcher) {
            $event = new Dispatcher($event, $params);
        }

        $event->setAttributes($this->attributes);
        $event->setAttribute('dispatcher', $this);
        
        $params = array_merge($params ?:[], [$event]);
        if (false !== strpos($event->getName(), ':')) {
            $namespace = substr($event->getName(), 0, strpos($event->getName(), ':'));
            if (isset($this->listeners[$namespace])) {
                $this->fire($this->listeners[$namespace], $event, $params, $callback);
            }
        }

        if (isset($this->listeners[$event->getName()])) {
            
            $this->fire($this->listeners[$event->getName()], $event, $params, $callback);
        }
        return $event;
    }

    /**
     * Alias for dispatch
     * 
     * @param  string|Event  $event
     * @param  array  $parameters
     *
     * @return Event $event
     */
    public function trigger($event = null, $parameters = null, $callback = null)
    {
        return $this->dispatch($event, $parameters, $callback);
    }

    /**
     * Organise parameters to suit both two and three arguments as a user dispatches or triggers the event
     * 
     * @param $parameters
     * 
     * @return array $items
     */
    protected function getparameters($parameters = null)
    {
        $items = [];
        if ($parameters instanceof Closure) {
            $items['params'] = [];
            $items['callback'] = $parameters;
        } else {
            $items['params'] = $parameters;
            $items['callback'] = null;
        }

        return $items;
    }

    /**
     * Fire an Event
     *
     * @param  array $listeners
     * @param  Dispatcher $event
     * @param array $params
     * @param callable|null $callback
     *
     * @return void
     */
    protected function fire($listeners, $event, array $params = [], $callback = null)
    {
        ksort($listeners);
        foreach ($listeners as $list) {
            foreach ($list as $listener) {
                $eventParams = $params;
                if (is_array($listener)) {
                    $eventListener = $listener;
                } elseif ($listener instanceof Closure) {
                    $eventListener = $listener;
                } else {
                    $eventListener = [$listener, 'handle'];
                    if ($listener instanceof HandlerInterface) {
                        if (array_values($params) !== $params) {
                            $event->setAttributes($params);
                            $eventParams = [$event];
                        }
                    }
                }
                call_user_func_array($eventListener, $eventParams);
                if($callback instanceof Closure)
                    $callback($event);
            }
        }
    }
}