<?php

namespace Yuga\Queue;

use Yuga\Interfaces\Queue\ShouldQueueInterface;

trait DispatchableTrait
{

    /**
     * Dispatch the job with the given arguments.
     *
     * @return mixed
     */
    public static function dispatch()
    {
        $args = func_get_args();

        // return new PendingDispatch(new static(...$args));
        return (new static(...$args))->queueOrNot();
    }

    protected function queueOrNot()
    {
        if ($this instanceof ShouldQueueInterface) {
            $queue = app('queue');
            // return $this;
            
            return $queue->createPayload($this);
        } else {
            return app()->call([$this, 'run']);
        }
    }
}
