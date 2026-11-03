<?php

declare(strict_types=1);

namespace Yuga\Queue;

trait DispatchableTrait
{
    public static function dispatch(mixed ...$arguments): mixed
    {
        return app('queue.dispatcher')->dispatch(
            new static(...$arguments)
        );
    }

    public static function dispatchNow(mixed ...$arguments): mixed
    {
        return app('queue.dispatcher')->dispatchNow(
            new static(...$arguments)
        );
    }

    public static function prepare(mixed ...$arguments): PendingDispatch
    {
        return app('queue.dispatcher')->prepare(
            new static(...$arguments)
        );
    }
}
