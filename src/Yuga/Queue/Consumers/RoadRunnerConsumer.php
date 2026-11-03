<?php

declare(strict_types=1);

namespace Yuga\Queue\Consumers;

use InvalidArgumentException;
use Spiral\RoadRunner\Jobs\Consumer;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;
use Throwable;
use UnexpectedValueException;
use Yuga\Queue\Contracts\FailureStoreInterface;
use Yuga\Queue\JobExecutor;
use Yuga\Queue\LifecycleReporter;
use Yuga\Queue\TerminalFailureNotifier;
use Yuga\Queue\Transport\RoadRunnerEnvelopeMapper;

final class RoadRunnerConsumer
{
    public function __construct(
        private readonly Consumer $consumer,
        private readonly RoadRunnerEnvelopeMapper $mapper,
        private readonly JobExecutor $executor,
        private readonly LifecycleReporter $reporter,
        private readonly FailureStoreInterface $failureStore,
        private readonly TerminalFailureNotifier $failureNotifier,
        private readonly string $failureSource,
        private readonly int $maxAttempts = 3,
        private readonly int $retryDelaySeconds = 5
    ) {
        if (trim($failureSource) === '') {
            throw new InvalidArgumentException(
                'A failure source is required.'
            );
        }

        if ($maxAttempts < 1 || $retryDelaySeconds < 0) {
            throw new InvalidArgumentException(
                'Invalid maximum attempts or retry delay.'
            );
        }
    }

    public function run(): void
    {
        while ($task = $this->consumer->waitTask()) {
            $this->captureOutput(
                fn() => $this->process($task)
            );
        }
    }

    private function process(ReceivedTaskInterface $task): void
    {
        // Invalid metadata escapes without deliberately discarding
        // the task. Raw-message quarantine remains a separate feature.
        $attempt = $this->attempt($task);
        $envelope = $this->mapper->fromTask($task);

        $context = [
            'job_id' => $envelope->id,
            'type' => $envelope->type,
            'encoding' => $envelope->encoding,
            'attempt' => $attempt,
            'task_id' => $task->getId(),
            'pipeline' => $task->getPipeline(),
            'source' => $this->failureSource,
        ];

        $payload = null;
        $executionError = null;

        try {
            if ($attempt > $this->maxAttempts) {
                throw new UnexpectedValueException(
                    'Task exceeds the configured attempt limit.'
                );
            }

            $this->reporter->report('queue:processing', $context);

            $payload = $this->executor->decode($envelope);

            // RoadRunner reservation context is not implemented yet.
            $this->executor->execute($envelope, $payload);
        } catch (Throwable $exception) {
            $executionError = $exception;
        }

        if ($executionError === null) {
            $task->ack();

            $this->reporter->report(
                'queue:successful',
                $context
            );

            return;
        }

        if ($attempt < $this->maxAttempts) {
            $task
                ->withHeader('yuga-attempt', (string) ($attempt + 1))
                ->withDelay($this->retryDelaySeconds)
                ->requeue($executionError->getMessage());

            $this->reporter->report('queue:retrying', [
                ...$context,
                'exception' => $executionError,
                'retry_delay_seconds' => $this->retryDelaySeconds,
                'next_attempt' => $attempt + 1,
            ]);

            return;
        }

        $failureId = $this->failureStore->record(
            source: $this->failureSource,
            deliveryId: $task->getId(),
            queue: $task->getPipeline(),
            envelope: $envelope,
            attempts: $attempt,
            reason: $executionError::class
                . ': ' . $executionError->getMessage()
        );

        // Publication/settlement errors escape the execution catch.
        $task->nack($executionError->getMessage());

        $this->failureNotifier->notify(
            envelope: $envelope,
            payload: $payload,
            attempt: $attempt,
            exception: $executionError,
            metadata: [
                'task_id' => $task->getId(),
                'pipeline' => $task->getPipeline(),
                'source' => $this->failureSource,
                'failure_id' => $failureId,
            ]
        );
    }

    private function attempt(ReceivedTaskInterface $task): int
    {
        $values = array_values($task->getHeader('yuga-attempt'));

        if ($values === []) {
            return 1;
        }

        if (
            count($values) !== 1
            || !is_string($values[0])
            || !preg_match('/^[1-9][0-9]*$/', $values[0])
        ) {
            throw new UnexpectedValueException(
                'Invalid yuga-attempt header.'
            );
        }

        $attempt = filter_var($values[0], FILTER_VALIDATE_INT);

        if ($attempt === false) {
            throw new UnexpectedValueException(
                'Attempt number exceeds the supported integer range.'
            );
        }

        return $attempt;
    }

    private function captureOutput(callable $callback): mixed
    {
        $initialLevel = ob_get_level();
        ob_start();

        try {
            return $callback();
        } finally {
            while (ob_get_level() > $initialLevel) {
                $output = ob_get_clean();

                if (is_string($output) && $output !== '') {
                    fwrite(STDERR, $output);
                }
            }
        }
    }
}
