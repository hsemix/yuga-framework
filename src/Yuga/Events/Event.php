<?php
namespace Yuga\Events;

use Closure;
use SPLSubject;
use SPLObserver;
use SplPriorityQueue;
use Yuga\EventHandlers\Handler;
use Yuga\Agree\Events\Dispatcher;
use Yuga\EventHandlers\HandlerInterface;
use Yuga\Shared\Controller as SharedController;

class Event implements SPLObserver, Dispatcher
{
    use SharedController;
    
    private $name;
    private $params;
    private $events;
    protected $handlers = [];

    public function __construct()
    {
        $this->events = new SPLPriorityQueue;
        if (method_exists($this, 'init')) {
            $this->init();
        }
    }

    public function attach()
    {
        $args = func_get_args();
        if (count($args) == 1) {
            $handlers = $args[0];
            
            if (is_array($handlers)) {
                foreach ($handlers as $handler) {
                    if (!$handler instanceof HandlerInterface) {
                        continue;
                    }
                    $this->handlers[] = $handler;
                }

                return;
            }

            if (!$handlers instanceof HandlerInterface) {
                return;
            }

            $this->handlers[] = $handlers;
        } else {
            if (count($args) == 2) {
                $name = $args[0];
                $callback = $args[1];
                $priority = 1;
            } elseif (count($args) == 3) {
                $name = $args[0];
                $callback = $args[1];
                $priority = $args[2];
            }
            $this->setName($name);
            $this->events->insert([$name, $callback], $priority);
        }
        return $this;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function dispatch($args = null)
    {
        $args = func_get_args();
        
        if (count($args) == 0) {
            foreach ($this->handlers as $handler) {
                $handler->handle($this);
            }
        } else {
            if (count($args) == 1) {
                $name = $args[0];
                $params = [];
                $callback = null;
            } elseif (count($args) == 2) {
                $name = $args[0];
                $params = $args[1];
                $callback = null;
            } elseif (count($args) == 3) {
                $name = $args[0];
                $params = $args[1];
                $callback = $args[2];
            }
            $arguments = array_merge($params, [$this]);
            
            foreach ($this->events as $event) {
                if ($event[0] == $name) {
                    call_user_func_array($event[1], $arguments);
                    if($callback instanceof Closure)
                        $callback($this);
                    
                }
            }
        }
        return $this;
    }

    /**
    * TO DO:...
    */
    public function update(SPLSubject $subject)
    {

    }

    public function fire($event, $options = [])
    {
        $this->dispatch($event, $options);
        return $this;
    }
}