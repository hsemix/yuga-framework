<?php
namespace Yuga\Agree\Events;

interface Dispatcher
{
    public function setName($name);

    public function getName();

    public function dispatch($args = null);
}