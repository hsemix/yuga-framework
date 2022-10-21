<?php

namespace Yuga\Database\Query;

use Yuga\Database\Query\Exceptions\DatabaseQueryException;
use Yuga\Database\Query\Exceptions\TransactionHaltException;

/**
 * Class Transaction.
 */
class Transaction extends Builder
{
    protected $transactionStatement;

    /**
     * @param \Closure $callback
     *
     * @return static
     */
    public function transaction(\Closure $callback): Transaction
    {
        $callback($this);

        return $this;
    }

    /**
     * Commit transaction.
     *
     * @throws \Yuga\Database\Query\Exceptions\TableNotFoundException
     * @throws \Yuga\Database\Query\Exceptions\ConnectionException
     * @throws \Yuga\Database\Query\Exceptions\ColumnNotFoundException
     * @throws \Yuga\Database\Query\Exception
     * @throws \Yuga\Database\Query\Exceptions\DuplicateColumnException
     * @throws \Yuga\Database\Query\Exceptions\DuplicateEntryException
     * @throws \Yuga\Database\Query\Exceptions\DuplicateKeyException
     * @throws \Yuga\Database\Query\Exceptions\ForeignKeyException
     * @throws \Yuga\Database\Query\Exceptions\NotNullException
     * @throws TransactionHaltException
     */
    public function commit(): void
    {
        try {
            $this->pdo->commit();
        } catch (\PDOException $e) {
            throw DatabaseQueryException::create($e, $this->getConnection()->getAdapterInstance(), $this->getLastQuery());
        }

        throw new TransactionHaltException('Commit triggered transaction-halt.');
    }

    /**
     * Rollback transaction.
     *
     * @throws \Yuga\Database\Query\Exceptions\TableNotFoundException
     * @throws \Yuga\Database\Query\Exceptions\ConnectionException
     * @throws \Yuga\Database\Query\Exceptions\ColumnNotFoundException
     * @throws \Yuga\Database\Query\Exception
     * @throws \Yuga\Database\Query\Exceptions\DuplicateColumnException
     * @throws \Yuga\Database\Query\Exceptions\DuplicateEntryException
     * @throws \Yuga\Database\Query\Exceptions\DuplicateKeyException
     * @throws \Yuga\Database\Query\Exceptions\ForeignKeyException
     * @throws \Yuga\Database\Query\Exceptions\NotNullException
     * @throws TransactionHaltException
     */
    public function rollBack(): void
    {
        try {
            $this->pdo->rollBack();
        } catch (\PDOException $e) {
            throw DatabaseQueryException::create($e, $this->getConnection()->getAdapterInstance(), $this->getLastQuery());
        }

        throw new TransactionHaltException('Rollback triggered transaction-halt.');
    }

    /**
     * Execute statement.
     *
     * @param string $sql
     * @param array  $bindings
     *
     * @throws \Yuga\Database\Query\Exceptions\TableNotFoundException
     * @throws \Yuga\Database\Query\Exceptions\ConnectionException
     * @throws \Yuga\Database\Query\Exceptions\ColumnNotFoundException
     * @throws \Yuga\Database\Query\Exception
     * @throws \Yuga\Database\Query\Exceptions\DuplicateColumnException
     * @throws \Yuga\Database\Query\Exceptions\DuplicateEntryException
     * @throws \Yuga\Database\Query\Exceptions\DuplicateKeyException
     * @throws \Yuga\Database\Query\Exceptions\ForeignKeyException
     * @throws \Yuga\Database\Query\Exceptions\NotNullException
     * @throws Exception
     *
     * @return array PDOStatement and execution time as float
     */
    public function statement(string $sql, array $bindings = []): array
    {
        if ($this->transactionStatement === null && $this->pdo->inTransaction() === true) {
            $results = parent::statement($sql, $bindings);
            $this->transactionStatement = $results[0];

            return $results;
        }

        return parent::statement($sql, $bindings);
    }
}
