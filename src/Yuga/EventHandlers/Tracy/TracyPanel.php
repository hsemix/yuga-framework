<?php

namespace Yuga\EventHandlers\Tracy;

use Tracy\Debugger;
use Yuga\Database\Tracy\DatabasePanel;
use Yuga\EventHandlers\HandlerInterface;
use Yuga\Views\Tracy\HaxExtensionsPanel;

class TracyPanel implements HandlerInterface
{
    /**
     * Hook into the tracy Debugger
     * 
     * @param \Yuga\Events\Event $event
     */
    public function handle($event)
    {
        if (!empty(env('DATABASE_NAME'))) {
            Debugger::getBar()->addPanel(new DatabasePanel);
        }
        
        // Debugger::getBar()->addPanel(new HaxExtensionsPanel);

        // return $event->trigger('on:yuga-tracy-hax');
    }
}