<?php

namespace Yuga\EventHandlers\Hax;

use Yuga\EventHandlers\HandlerInterface;

class HaxInstance implements HandlerInterface
{
    /**
     * Hook into the view engine before a the compiler is instantiated
     * ---------------------------------------------------------------------------
     * Change the views directory, Change views Cache directory.                 |
     * ---------------------------------------------------------------------------
     * Do much more.
     */
    public function handle($event)
    {
    }
}
