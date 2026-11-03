<?php

declare(strict_types=1);

namespace Yuga\Queue;

use Closure;
use Throwable;

final class LifecycleReporter
{
    /** @var list<Closure> */
    private array $listeners = [];

    public function listen(callable $listener): self
    {
        $this->listeners[] = Closure::fromCallable($listener);

        return $this;
    }

    public function report(string $event, array $context): void
    {
        foreach ($this->listeners as $listener) {
            try {
                $listener($event, $context);
            } catch (Throwable $exception) {
                // Reporting errors cannot change the job's outcome.
                error_log(
                    'Queue lifecycle listener failed: '
                        . $exception::class . ': '
                        . $exception->getMessage()
                );
            }
        }
    }
}
