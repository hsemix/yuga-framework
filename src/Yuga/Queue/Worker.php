<?php

declare(strict_types=1);

namespace Yuga\Queue;

use InvalidArgumentException;
use Throwable;
use Yuga\Queue\Contracts\QueueTransportInterface;
use Yuga\Queue\Exceptions\ReservationLostException;
use Yuga\Queue\Payload\CodecRegistry;
use Yuga\Queue\Transport\JobReservation;

final class Worker
{
    private readonly JobExecutor $executor;

    public function __construct(
        private readonly QueueTransportInterface $transport,
        private readonly CodecRegistry $codecs,
        private readonly HandlerRegistry $handlers,
        private readonly int $maxAttempts = 3,
        private readonly int $retryDelaySeconds = 5,
        private readonly int $reservationSeconds = 60,
        private readonly ?LifecycleReporter $reporter = null
    ) {
        if ($maxAttempts < 1) {
            throw new InvalidArgumentException(
                'Maximum attempts must be positive.'
            );
        }

        if ($retryDelaySeconds < 0 || $reservationSeconds < 1) {
            throw new InvalidArgumentException(
                'Invalid retry delay or reservation duration.'
            );
        }

        $this->executor = new JobExecutor($codecs, $handlers);
    }

    public function runOnce(string $queue = 'default'): WorkerResult
    {
        $reservation = $this->transport->reserve(
            $queue,
            $this->reservationSeconds
        );

        if ($reservation === null) {
            return WorkerResult::Idle;
        }

        if ($reservation->attempt > $this->maxAttempts) {
            return $this->markFailed(
                $reservation,
                new \RuntimeException(
                    'Maximum attempts exceeded after reservation recovery.'
                ),
                null
            );
        }

        $payload = null;

        $this->report('queue:processing', $reservation);

        try {
            $envelope = $reservation->envelope;

            // $payload = $envelope->decode(
            //     $this->codecs->get($envelope->encoding)
            // );

            // $this->handlers->execute($envelope, $payload, new JobContext($this->transport, $reservation));

            $payload = $this->executor->decode($envelope);

            $this->executor->execute(
                $envelope,
                $payload,
                new JobContext($this->transport, $reservation)
            );
        } catch (ReservationLostException $exception) {
            $this->report('queue:reservation-lost', $reservation, [
                'exception' => $exception,
            ]);

            throw $exception;
        } catch (Throwable $exception) {
            if ($reservation->attempt >= $this->maxAttempts) {
                return $this->markFailed(
                    $reservation,
                    $exception,
                    $payload
                );
            }

            $this->requireOwnership(
                $this->transport->release(
                    $reservation,
                    $this->retryDelaySeconds
                ),
                $reservation
            );

            $this->report('queue:retrying', $reservation, [
                'exception' => $exception,
                'retry_delay_seconds' => $this->retryDelaySeconds,
            ]);

            return WorkerResult::Released;
        }

        $this->requireOwnership(
            $this->transport->acknowledge($reservation),
            $reservation
        );

        $this->report('queue:successful', $reservation);

        return WorkerResult::Completed;
    }

    private function markFailed(
        JobReservation $reservation,
        Throwable $exception,
        mixed $payload
    ): WorkerResult {
        $this->requireOwnership(
            $this->transport->fail(
                $reservation,
                $exception::class . ': ' . $exception->getMessage()
            ),
            $reservation
        );

        $this->report('queue:failure', $reservation, [
            'exception' => $exception,
        ]);

        if ($payload instanceof CanFailInterface) {
            // Keep the old callback's id/attempts property convention.
            // id is now the portable envelope ID, not a database row ID.
            $context = (object) [
                'id' => $reservation->envelope->id,
                'attempts' => $reservation->attempt,
                'type' => $reservation->envelope->type,
            ];

            try {
                $payload->onFailure(
                    $context,
                    $exception->getMessage()
                );
            } catch (Throwable $callbackException) {
                $this->report(
                    'queue:callback-failure',
                    $reservation,
                    ['exception' => $callbackException]
                );
            }
        }

        return WorkerResult::Failed;
    }

    private function requireOwnership(
        bool $succeeded,
        JobReservation $reservation
    ): void {
        if (!$succeeded) {
            $exception = new ReservationLostException(
                'Reservation ownership was lost for job '
                    . "[{$reservation->envelope->id}]."
            );

            $this->report('queue:reservation-lost', $reservation, [
                'exception' => $exception,
            ]);

            throw $exception;
        }
    }

    private function report(
        string $event,
        JobReservation $reservation,
        array $extra = []
    ): void {
        $this->reporter?->report($event, [
            'job_id' => $reservation->envelope->id,
            'type' => $reservation->envelope->type,
            'encoding' => $reservation->envelope->encoding,
            'attempt' => $reservation->attempt,
            ...$extra,
        ]);
    }
}
