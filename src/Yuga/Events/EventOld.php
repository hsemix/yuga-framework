<?php
namespace Yuga\Events;

use Closure;
use Yuga\EventHandlers\HandlerInterface;
use Yuga\Events\Exceptions\EventException;
use Yuga\Events\Dispatcher\Event as Dispatcher;
use Yuga\Interfaces\Events\Dispatcher as IDispatcher;

class EventOld implements IDispatcher
{
    /**
     * @var array list of listeners
     */
    protected $listeners = [];

    /**
     * @var string name of the default events
     */
    protected $name = 'yuga.auto.events';

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
                if (!$handler instanceof HandlerInterface) {
                    throw new EventException(get_class($handler).' must implement the `'. HandlerInterface::class .'` interface');
                }
                $this->listeners[$this->name][1][] = [$handler, 'handle'];
            }
            return;
        }
        if (!$handlers instanceof HandlerInterface) {
            throw new EventException(get_class($handlers).' must implement the `'. HandlerInterface::class .'` interface');
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
                call_user_func_array($listener, $params);
                if($callback instanceof Closure)
                    $callback($event);
            }
        }
    }
}