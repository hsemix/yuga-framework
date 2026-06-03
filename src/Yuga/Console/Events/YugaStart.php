<?php

declare(strict_types=1);

namespace Yuga\Console\Events;

use Yuga\Events\Event;
use Yuga\Interfaces\Application\Application;

class YugaStart extends Event
{
    /**
     * The Yuga application instance.
     *
     * @var \Yuga\Interfaces\Application\Application
     */
    public $yuga;

    /**
     * Create a new event instance.
     */
    public function __construct(Application $yuga)
    {
        $this->yuga = $yuga;
    }
}
