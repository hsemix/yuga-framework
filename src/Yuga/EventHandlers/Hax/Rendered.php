<?php

namespace Yuga\EventHandlers\Hax;

use Yuga\EventHandlers\HandlerInterface;

class Rendered implements HandlerInterface
{
    /**
     * Hook into the view engine before a page is rendered
     * ---------------------------------------------------------------------------
     * Set global variables that can be accessed accrosed the entire application.|
     * ---------------------------------------------------------------------------
     * Do much more.
     */
    public function handle($event)
    {
    }
}
