<?php
namespace Yuga\Console\Events;

use Yuga\Application;
use Yuga\Events\Event;

class YugaStart extends Event
{
    /**
     * The Yuga application instance.
     *
     * @var \Yuga\Application
     */
    public $yuga;

    /**
     * Create a new event instance.
     *
     * @param  \Yuga\Application  $yuga
     * @return void
     */
    public function __construct(Application $yuga)
    {
        parent::__construct();
        $this->yuga = $yuga;
    }
}