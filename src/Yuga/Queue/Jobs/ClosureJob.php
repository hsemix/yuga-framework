<?php

declare(strict_types=1);

namespace Yuga\Queue\Jobs;

use Closure;
use Yuga\Interfaces\Queue\ShouldQueueInterface;
use Yuga\Queue\CanFailInterface;
use Yuga\Queue\DispatchableTrait;
use Yuga\Queue\JobContext;

final class ClosureJob implements ShouldQueueInterface, CanFailInterface
{
    use DispatchableTrait;

    public function __construct(
        public Closure $callback,
        public mixed $data = [],
        public ?Closure $failureCallback = null
    ) {}

    public function run(?JobContext $context = null): mixed
    {
        return ($this->callback)($this->data, $context);
    }

    public function onFailure($job, $message)
    {
        if ($this->failureCallback !== null) {
            return ($this->failureCallback)($job, $message);
        }

        return null;
    }
}
