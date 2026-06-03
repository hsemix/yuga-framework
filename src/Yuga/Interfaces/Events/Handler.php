<?php

declare(strict_types=1);

namespace Yuga\Interfaces\Events;

interface Handler
{
    public function handle($event);
}
