<?php

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
     *
     * @param \Yuga\Interfaces\Application\Application $yuga
     *
     * @return void
     */
    public function __construct(Application $yuga)
    {
        $this->yuga = $yuga;
    }
}
