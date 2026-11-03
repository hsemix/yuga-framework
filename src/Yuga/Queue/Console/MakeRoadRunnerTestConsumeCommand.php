<?php

declare(strict_types=1);

namespace Yuga\Queue\Console;

use InvalidArgumentException;
use Spiral\RoadRunner\Jobs\Consumer;
use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;
use Throwable;
use UnexpectedValueException;
use Yuga\Console\Command;
use Yuga\Queue\Transport\RoadRunnerEnvelopeMapper;

class MakeRoadRunnerTestConsumeCommand //extends Command
{
    protected $name = 'queue:roadrunner-test';

    protected $description = 'Test Yuga job execution and retries through RoadRunner.';

    public function handle()
    {
        $settings = config('queue_transport')['worker'] ?? [];

        $maxAttempts = $settings['max_attempts'] ?? 3;
        $retryDelay = $settings['retry_delay'] ?? 5;

        if (!is_int($maxAttempts) || $maxAttempts < 1) {
            throw new InvalidArgumentException(
                'Maximum attempts must be a positive integer.'
            );
        }

        if (!is_int($retryDelay) || $retryDelay < 0) {
            throw new InvalidArgumentException(
                'Retry delay must be a nonnegative integer.'
            );
        }

        $consumer = new Consumer();
        $mapper = new RoadRunnerEnvelopeMapper();
        $executor = app('queue.executor');

        $failureStore = app('queue.failures');
        $failureSource = 'fntyuga-roadrunner-test';

        while ($task = $consumer->waitTask()) {
            try {
                $attempt = $this->attempt($task);
            } catch (Throwable $exception) {
                $this->log('invalid', [
                    'task_id' => $task->getId(),
                    'message' => $exception->getMessage(),
                ]);

                // Do not discard malformed delivery metadata.
                throw $exception;
            }

            $envelope = null;
            $executionError = null;


            $initialLevel = ob_get_level();
            ob_start();

            try {
                $envelope = $mapper->fromTask($task);

                $this->report('queue:processing', [
                    'job_id' => $envelope->id,
                    'type' => $envelope->type,
                    'encoding' => $envelope->encoding,
                    'attempt' => $attempt,
                    'task_id' => $task->getId(),
                    'pipeline' => $task->getPipeline(),
                ]);



                if ($attempt > $maxAttempts) {
                    throw new UnexpectedValueException(
                        'Task exceeds the configured attempt limit.'
                    );
                }

                $payload = $executor->decode($envelope);

                $executor->execute($envelope, $payload);
            } catch (Throwable $exception) {
                $executionError = $exception;
            } finally {
                while (ob_get_level() > $initialLevel) {
                    $output = ob_get_clean();

                    if (is_string($output) && $output !== '') {
                        fwrite(STDERR, $output);
                    }
                }
            }

            $context = [
                'job_id' => $envelope?->id,
                'type' => $envelope?->type,
                'encoding' => $envelope?->encoding,
                'task_id' => $task->getId(),
                'pipeline' => $task->getPipeline(),
                'attempt' => $attempt,
            ];

            if ($executionError === null) {
                // Settlement errors must escape rather than trigger
                // another response for the same delivery.
                $task->ack();

                $this->report('queue:successful', $context);
                $this->log('completed', $context);

                continue;
            }

            $context['exception'] = $executionError::class;
            $context['message'] = $executionError->getMessage();

            if ($attempt < $maxAttempts) {
                $task
                    ->withHeader('yuga-attempt', (string) ($attempt + 1))
                    ->withDelay($retryDelay)
                    ->requeue($executionError->getMessage());


                $this->report('queue:retrying', [
                    ...$context,
                    'exception' => $executionError,
                    'retry_delay_seconds' => $retryDelay,
                    'next_attempt' => $attempt + 1,
                ]);

                $this->log('retrying', [
                    ...$context,
                    'next_attempt' => $attempt + 1,
                    'retry_delay_seconds' => $retryDelay,
                ]);

                continue;
            }

            // Temporary test policy: reject without requesting redelivery.
            // Durable failure storage will be added next.
            if ($envelope === null) {
                $this->log('invalid', $context);

                // Raw-message quarantine is not implemented yet.
                throw $executionError;
            }

            $failureId = $failureStore->record(
                source: $failureSource,
                deliveryId: $task->getId(),
                queue: $task->getPipeline(),
                envelope: $envelope,
                attempts: $attempt,
                reason: $executionError::class . ': ' . $executionError->getMessage()
            );

            // Persistence succeeded; now reject the terminal task.
            $task->nack($executionError->getMessage());

            $this->captureOutput(
                fn() => app('queue.failure-notifier')->notify(
                    envelope: $envelope,
                    payload: $payload,
                    attempt: $attempt,
                    exception: $executionError,
                    metadata: [
                        'task_id' => $task->getId(),
                        'pipeline' => $task->getPipeline(),
                        'failure_id' => $failureId,
                    ]
                )
            );

            $this->log('failed', [
                ...$context,
                'failure_id' => $failureId,
            ]);
        }

        return self::SUCCESS;
    }

    private function attempt(ReceivedTaskInterface $task): int
    {
        $values = array_values($task->getHeader('yuga-attempt'));

        // Accept messages published before the header was introduced.
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
                'The attempt number exceeds the supported integer range.'
            );
        }

        return $attempt;
    }

    private function log(string $event, array $context): void
    {
        fwrite(STDERR, json_encode([
            'event' => 'yuga.roadrunner.test.' . $event,
            ...$context,
        ], JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE) . PHP_EOL);
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

    private function report(string $event, array $context): void
    {
        $this->captureOutput(
            fn() => app('queue.reporter')->report($event, $context)
        );
    }
}
