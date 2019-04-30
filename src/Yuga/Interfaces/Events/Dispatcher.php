<?php
namespace Yuga\Interfaces\Events;

interface Dispatcher
{
    public function dispatch($event = null, $parameters = null, $callback = null);
}