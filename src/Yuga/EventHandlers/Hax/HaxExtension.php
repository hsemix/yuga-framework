<?php

namespace Yuga\EventHandlers\Hax;

use Yuga\EventHandlers\HandlerInterface;

class HaxExtension implements HandlerInterface
{
    /**
     * Hook into the view engine before a page is rendered
     * ---------------------------------------------------------------------------
     * Set global variables that can be accessed accrosed the entire application.|
     * ---------------------------------------------------------------------------
     * Do much more
     * @param \Yuga\Events\Event $event
     */
    public function handle($event)
    {
        
    }
}