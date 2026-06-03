<?php

declare(strict_types=1);

namespace Yuga\EventHandlers;

use Yuga\Events\Event;

interface HandlerInterface
{
    public function handle($event);
}
