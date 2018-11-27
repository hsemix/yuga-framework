<?php
namespace Yuga\EventHandlers;

use Yuga\Events\Event;

interface HandlerInterface
{
    public function handle($event);
}