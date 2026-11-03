<?php

declare(strict_types=1);

namespace Yuga\Queue\Failure;

use InvalidArgumentException;
use PDO;
use PDOException;
use Yuga\Queue\Contracts\FailureStoreInterface;
use Yuga\Queue\Contracts\InspectableFailureStoreInterface;
use Yuga\Queue\Contracts\ReplayableFailureStoreInterface;
use Yuga\Queue\Exceptions\TransportException;
use Yuga\Queue\Payload\JobEnvelope;

final class DatabaseFailureStore implements ReplayableFailureStoreInterface
{
    private readonly string $table;

    public function __construct(
        private readonly PDO $pdo,
        string $table = 'queue_failures'
    ) {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
            throw new InvalidArgumentException(
                'This failure store currently supports MySQL.'
            );
        }

        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $table)) {
            throw new InvalidArgumentException(
                'Invalid failure table name.'
            );
        }

        $this->table = '`' . $table . '`';
    }

    public function record(
        string $source,
        string $deliveryId,
        string $queue,
        JobEnvelope $envelope,
        int $attempts,
        string $reason
    ): string {
        if (
            trim($source) === ''
            || trim($deliveryId) === ''
            || trim($queue) === ''
            || $attempts < 1
        ) {
            throw new InvalidArgumentException(
                'Source, delivery ID, queue, and positive attempts are required.'
            );
        }

        // Do not acknowledge work whose persistence is still
        // inside an uncommitted application transaction.
        if ($this->pdo->inTransaction()) {
            throw new TransportException(
                'Failure storage requires an autocommit connection.'
            );
        }

        $id = hash(
            'sha256',
            strlen($source) . ':' . $source . $deliveryId
        );

        try {
            $statement = $this->pdo->prepare(
                "INSERT INTO {$this->table}
                    (id, source, delivery_id, queue,
                     job_id, type, encoding, version, body,
                     attempts, reason, failed_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE id = id"
            );

            if (
                $statement === false
                || !$statement->execute([
                    $id,
                    $source,
                    $deliveryId,
                    $queue,
                    $envelope->id,
                    $envelope->type,
                    $envelope->encoding,
                    $envelope->version,
                    $envelope->body,
                    $attempts,
                    $reason,
                    time(),
                ])
            ) {
                throw new TransportException(
                    'Could not persist terminal queue failure.'
                );
            }
        } catch (PDOException $exception) {
            throw new TransportException(
                'Could not persist terminal queue failure.',
                previous: $exception
            );
        }

        return $id;
    }


    public function find(string $failureId): ?StoredFailure
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $failureId)) {
            throw new InvalidArgumentException('Invalid failure ID.');
        }

        $row = $this->query(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$failureId]
        )->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->fromRow($row);
    }

    public function listing(
        ?string $source = null,
        int $limit = 50,
        int $offset = 0
    ): array {
        if ($limit < 1 || $limit > 1000 || $offset < 0) {
            throw new InvalidArgumentException(
                'Limit must be 1–1000 and offset must be nonnegative.'
            );
        }

        if ($source !== null && trim($source) === '') {
            throw new InvalidArgumentException('Source must not be empty.');
        }

        $where = $source === null ? '' : ' WHERE source = ?';
        $bindings = $source === null ? [] : [$source];

        $rows = $this->query(
            "SELECT * FROM {$this->table}{$where}
         ORDER BY failed_at DESC, id DESC
         LIMIT {$limit} OFFSET {$offset}",
            $bindings
        )->fetchAll(PDO::FETCH_ASSOC);

        return array_map(
            fn(array $row): StoredFailure => $this->fromRow($row),
            $rows
        );
    }

    private function fromRow(array $row): StoredFailure
    {
        return new StoredFailure(
            id: $row['id'],
            source: $row['source'],
            deliveryId: $row['delivery_id'],
            destination: $row['queue'],
            envelope: new JobEnvelope(
                id: $row['job_id'],
                type: $row['type'],
                encoding: $row['encoding'],
                body: $row['body'],
                version: (int) $row['version']
            ),
            attempts: (int) $row['attempts'],
            reason: $row['reason'],
            failedAt: (int) $row['failed_at']
        );
    }

    private function query(
        string $sql,
        array $bindings
    ): \PDOStatement {
        try {
            $statement = $this->pdo->prepare($sql);

            if ($statement === false || !$statement->execute($bindings)) {
                throw new TransportException('Failure-store query failed.');
            }

            return $statement;
        } catch (PDOException $exception) {
            throw new TransportException(
                'Failure-store query failed.',
                previous: $exception
            );
        }
    }


    public function claimReplay(
        string $failureId,
        string $source
    ): ?ReplayClaim {
        if (!preg_match('/^[a-f0-9]{64}$/', $failureId)) {
            throw new InvalidArgumentException('Invalid failure ID.');
        }

        if (trim($source) === '') {
            throw new InvalidArgumentException('Source must not be empty.');
        }

        if ($this->pdo->inTransaction()) {
            throw new TransportException(
                'Replay claims require an autocommit connection.'
            );
        }

        $token = bin2hex(random_bytes(32));

        $statement = $this->query(
            "UPDATE {$this->table}
         SET replay_status = 'replaying',
             replay_token = ?,
             replay_started_at = ?
         WHERE id = ?
           AND source = ?
           AND replay_status = 'pending'",
            [$token, time(), $failureId, $source]
        );

        if ($statement->rowCount() !== 1) {
            return null;
        }

        $failure = $this->find($failureId);

        if ($failure === null) {
            throw new TransportException(
                'Claimed failure could not be loaded.'
            );
        }

        return new ReplayClaim($failure, $token);
    }

    public function markReplayed(ReplayClaim $claim): bool
    {
        if ($this->pdo->inTransaction()) {
            throw new TransportException(
                'Replay completion requires an autocommit connection.'
            );
        }

        return $this->query(
            "UPDATE {$this->table}
         SET replay_status = 'replayed',
             replayed_at = ?
         WHERE id = ?
           AND replay_status = 'replaying'
           AND replay_token = ?",
            [
                time(),
                $claim->failure->id,
                $claim->token,
            ]
        )->rowCount() === 1;
    }
}
