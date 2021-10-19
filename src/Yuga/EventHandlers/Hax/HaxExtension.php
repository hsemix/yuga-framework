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
        $event->attach('on:yuga-tracy', function ($event) {
            echo 'yes...';

            // $event->dispatch('on:yuga-tracy');
        });

        // die();

        // $event->dispatch();

        // return $event;

        // echo '<pre>';

        // print_r($event);

        // if (count(array_keys($event->extensions['keys'])) > 0) {
        //     echo '<pre>';
        //     print_r($event->extensions);
        //     die();
        // }
        // die();
    }
}