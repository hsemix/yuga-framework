<?php

declare(strict_types=1);

namespace Yuga\Queue;

use Throwable;
use Yuga\Queue\Payload\JobEnvelope;

final class TerminalFailureNotifier
{
    public function __construct(
        private readonly LifecycleReporter $reporter
    ) {}

    public function notify(
        JobEnvelope $envelope,
        mixed $payload,
        int $attempt,
        Throwable $exception,
        array $metadata = []
    ): void {
        $context = [
            ...$metadata,
            'job_id' => $envelope->id,
            'type' => $envelope->type,
            'encoding' => $envelope->encoding,
            'attempt' => $attempt,
            'exception' => $exception,
        ];

        $this->reporter->report('queue:failure', $context);

        if (!$payload instanceof CanFailInterface) {
            return;
        }

        $job = (object) [
            'id' => $envelope->id,
            'attempts' => $attempt,
            'type' => $envelope->type,
        ];

        try {
            $payload->onFailure($job, $exception->getMessage());
        } catch (Throwable $callbackException) {
            $this->reporter->report('queue:callback-failure', [
                ...$context,
                'exception' => $callbackException,
                'original_exception' => $exception,
            ]);
        }
    }
}
