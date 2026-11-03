<?php

declare(strict_types=1);

namespace Yuga\Queue\Transport;

use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use Yuga\Queue\Contracts\FailedJobTransportInterface;
use Yuga\Queue\Contracts\QueueTransportInterface;
use Yuga\Queue\Contracts\RenewableTransportInterface;
use Yuga\Queue\Exceptions\TransportException;
use Yuga\Queue\Payload\JobEnvelope;

final class DatabaseTransport implements QueueTransportInterface, FailedJobTransportInterface, RenewableTransportInterface
{
    private readonly string $table;

    public function __construct(
        private readonly PDO $pdo,
        string $table = 'queue_messages'
    ) {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
            throw new InvalidArgumentException('Invalid queue table name.');
        }

        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            throw new InvalidArgumentException(
                'This database transport currently supports MySQL.'
            );
        }

        $this->table = '`' . $table . '`';
    }

    public function publish(
        JobEnvelope $envelope,
        string $queue = 'default',
        int $delaySeconds = 0
    ): void {
        $this->validateQueue($queue);
        $this->validateDelay($delaySeconds);

        $now = time();

        $this->execute(
            "INSERT INTO {$this->table}
                (job_id, queue, type, encoding, version, body,
                 status, attempts, available_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, 'waiting', 0, ?, ?, ?)",
            [
                $envelope->id,
                $queue,
                $envelope->type,
                $envelope->encoding,
                $envelope->version,
                $envelope->body,
                $now + $delaySeconds,
                $now,
                $now,
            ]
        );
    }

    public function reserve(
        string $queue = 'default',
        int $reservationSeconds = 60
    ): ?JobReservation {
        $this->validateQueue($queue);

        if ($reservationSeconds < 1) {
            throw new InvalidArgumentException(
                'Reservation duration must be positive.'
            );
        }

        // Bound contention retries so this method cannot spin indefinitely.
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $now = time();

            $row = $this->execute(
                "SELECT *
                 FROM {$this->table}
                 WHERE queue = ?
                   AND (
                       (status = 'waiting' AND available_at <= ?)
                       OR
                       (status = 'reserved' AND reserved_until <= ?)
                   )
                 ORDER BY available_at, id
                 LIMIT 1",
                [$queue, $now, $now]
            )->fetch(PDO::FETCH_ASSOC);

            if ($row === false) {
                return null;
            }

            $token = bin2hex(random_bytes(32));

            // The UPDATE is the atomic claim. Reading the row alone
            // never grants permission to execute it.
            $claimed = $this->execute(
                "UPDATE {$this->table}
                 SET status = 'reserved',
                     attempts = attempts + 1,
                     reservation_token = ?,
                     reserved_until = ?,
                     updated_at = ?
                 WHERE id = ?
                   AND (
                       (status = 'waiting' AND available_at <= ?)
                       OR
                       (status = 'reserved' AND reserved_until <= ?)
                   )",
                [
                    $token,
                    $now + $reservationSeconds,
                    $now,
                    $row['id'],
                    $now,
                    $now,
                ]
            );

            if ($claimed->rowCount() !== 1) {
                continue;
            }

            // Read the actual attempt count after claiming.
            $owned = $this->execute(
                "SELECT *
                 FROM {$this->table}
                 WHERE id = ?
                   AND status = 'reserved'
                   AND reservation_token = ?
                   AND reserved_until > ?",
                [$row['id'], $token, time()]
            )->fetch(PDO::FETCH_ASSOC);

            if ($owned === false) {
                continue;
            }

            return new JobReservation(
                envelope: new JobEnvelope(
                    id: $owned['job_id'],
                    type: $owned['type'],
                    encoding: $owned['encoding'],
                    body: $owned['body'],
                    version: (int) $owned['version']
                ),
                receipt: [
                    'transport' => $this,
                    'id' => $owned['id'],
                    'token' => $token,
                ],
                attempt: (int) $owned['attempts']
            );
        }

        throw new TransportException(
            'Could not reserve a job after repeated claim contention.'
        );
    }

    public function acknowledge(JobReservation $reservation): bool
    {
        return $this->transition(
            $reservation,
            "status = 'done',
             reservation_token = NULL,
             reserved_until = NULL"
        );
    }

    public function release(
        JobReservation $reservation,
        int $delaySeconds = 0
    ): bool {
        $this->validateDelay($delaySeconds);

        return $this->transition(
            $reservation,
            "status = 'waiting',
             available_at = ?,
             reservation_token = NULL,
             reserved_until = NULL",
            [time() + $delaySeconds]
        );
    }

    public function fail(
        JobReservation $reservation,
        string $reason
    ): bool {
        return $this->transition(
            $reservation,
            "status = 'failed',
             failure_reason = ?,
             reservation_token = NULL,
             reserved_until = NULL",
            [$reason]
        );
    }

    private function transition(
        JobReservation $reservation,
        string $changes,
        array $bindings = []
    ): bool {
        $receipt = $reservation->receipt;

        if (
            !is_array($receipt)
            || ($receipt['transport'] ?? null) !== $this
            || !isset($receipt['id'], $receipt['token'])
        ) {
            throw new InvalidArgumentException(
                'The reservation belongs to another transport.'
            );
        }

        $now = time();

        $statement = $this->execute(
            "UPDATE {$this->table}
             SET {$changes}, updated_at = ?
             WHERE id = ?
               AND status = 'reserved'
               AND reservation_token = ?
               AND reserved_until > ?",
            [
                ...$bindings,
                $now,
                $receipt['id'],
                $receipt['token'],
                $now,
            ]
        );

        return $statement->rowCount() === 1;
    }

    private function execute(string $sql, array $bindings): PDOStatement
    {
        try {
            $statement = $this->pdo->prepare($sql);

            if ($statement === false || !$statement->execute($bindings)) {
                throw new TransportException(
                    'Queue database operation failed.'
                );
            }

            return $statement;
        } catch (PDOException $exception) {
            throw new TransportException(
                'Queue database operation failed.',
                previous: $exception
            );
        }
    }

    private function validateQueue(string $queue): void
    {
        if (trim($queue) === '') {
            throw new InvalidArgumentException(
                'Queue name must not be empty.'
            );
        }
    }

    private function validateDelay(int $seconds): void
    {
        if ($seconds < 0) {
            throw new InvalidArgumentException(
                'Delay must not be negative.'
            );
        }
    }

    public function failedJobs(
        ?string $queue = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        if ($limit < 1 || $limit > 1000 || $offset < 0) {
            throw new InvalidArgumentException(
                'Limit must be between 1 and 1000; offset must be nonnegative.'
            );
        }

        $where = "status = 'failed'";
        $bindings = [];

        if ($queue !== null) {
            $this->validateQueue($queue);
            $where .= ' AND queue = ?';
            $bindings[] = $queue;
        }

        // Limit and offset are validated integers, not user-provided SQL.
        $rows = $this->execute(
            "SELECT *
         FROM {$this->table}
         WHERE {$where}
         ORDER BY updated_at DESC, id DESC
         LIMIT {$limit} OFFSET {$offset}",
            $bindings
        )->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn(array $row): FailedJob => $this->failedJobFromRow($row),
            $rows
        );
    }

    public function findFailed(string $failureId): ?FailedJob
    {
        $this->validateFailureId($failureId);

        $row = $this->execute(
            "SELECT *
         FROM {$this->table}
         WHERE id = ? AND status = 'failed'",
            [$failureId]
        )->fetch(PDO::FETCH_ASSOC);

        return $row === false
            ? null
            : $this->failedJobFromRow($row);
    }

    public function retryFailed(
        string $failureId,
        int $delaySeconds = 0
    ): bool {
        $this->validateFailureId($failureId);
        $this->validateDelay($delaySeconds);

        $now = time();

        $statement = $this->execute(
            "UPDATE {$this->table}
         SET status = 'waiting',
             attempts = 0,
             available_at = ?,
             reservation_token = NULL,
             reserved_until = NULL,
             failure_reason = NULL,
             updated_at = ?
         WHERE id = ? AND status = 'failed'",
            [$now + $delaySeconds, $now, $failureId]
        );

        return $statement->rowCount() === 1;
    }

    public function forgetFailed(string $failureId): bool
    {
        $this->validateFailureId($failureId);

        return $this->execute(
            "DELETE FROM {$this->table}
         WHERE id = ? AND status = 'failed'",
            [$failureId]
        )->rowCount() === 1;
    }

    private function failedJobFromRow(array $row): FailedJob
    {
        return new FailedJob(
            failureId: (string) $row['id'],
            envelope: new JobEnvelope(
                id: $row['job_id'],
                type: $row['type'],
                encoding: $row['encoding'],
                body: $row['body'],
                version: (int) $row['version']
            ),
            queue: $row['queue'],
            attempts: (int) $row['attempts'],
            reason: (string) ($row['failure_reason'] ?? ''),
            failedAt: (int) $row['updated_at']
        );
    }

    private function validateFailureId(string $failureId): void
    {
        if (!preg_match('/^[1-9][0-9]*$/', $failureId)) {
            throw new InvalidArgumentException(
                'Database failure ID must be a positive integer string.'
            );
        }
    }

    public function renew(
        JobReservation $reservation,
        int $additionalSeconds
    ): bool {
        if ($additionalSeconds < 1) {
            throw new InvalidArgumentException(
                'Reservation extension must be positive.'
            );
        }

        return $this->transition(
            $reservation,
            'reserved_until = reserved_until + ?',
            [$additionalSeconds]
        );
    }

    public function statistics(?string $queue = null): array
    {
        $now = time();

        $where = '';
        $bindings = [$now, $now, $now, $now];

        if ($queue !== null) {
            $this->validateQueue($queue);
            $where = ' WHERE queue = ?';
            $bindings[] = $queue;
        }

        $row = $this->execute(
            "SELECT
            COUNT(*) AS total,
            COALESCE(SUM(status = 'waiting' AND available_at <= ?), 0) AS ready,
            COALESCE(SUM(status = 'waiting' AND available_at > ?), 0) AS delayed,
            COALESCE(SUM(status = 'reserved' AND reserved_until > ?), 0) AS reserved,
            COALESCE(SUM(status = 'reserved' AND reserved_until <= ?), 0) AS expired,
            COALESCE(SUM(status = 'failed'), 0) AS failed,
            COALESCE(SUM(status = 'done'), 0) AS completed
         FROM {$this->table}{$where}",
            $bindings
        )->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new \Yuga\Queue\Exceptions\TransportException(
                'Could not retrieve queue statistics.'
            );
        }

        return array_map(
            static fn($value): int => (int) $value,
            $row
        );
    }
}
