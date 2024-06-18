<?php
namespace Yuga\Database\Query\Exceptions;

use Exception;
use PDOException;
use Yuga\Database\Query\QueryObject;
use Yuga\Database\Query\Grammar\Mysql;
use Yuga\Database\Query\Grammar\Pgsql;
use Yuga\Database\Query\Grammar\Sqlite;
use Yuga\Database\Query\Exceptions\NotNullException;
use Yuga\Database\Query\Exceptions\ConnectionException;
use Yuga\Database\Query\Exceptions\ForeignKeyException;
use Yuga\Database\Query\Exceptions\DuplicateKeyException;
use Yuga\Database\Query\Exceptions\TableNotFoundException;
use Yuga\Database\Query\Exceptions\ColumnNotFoundException;
use Yuga\Database\Query\Exceptions\DuplicateEntryException;
use Yuga\Database\Query\Exceptions\DuplicateColumnException;

/**
 * Class Exception
 *
 * @package Yuga\Database\Query
 */
class DatabaseQueryException extends Exception
{
    protected $query;

    public function __construct($message = '', $code = 0, $previous = null, QueryObject $query = null)
    {
        $lastSql = $query ? PHP_EOL.PHP_EOL.'Last query: '. $query->getRawSql() : '';
        parent::__construct($message.$lastSql, $code, $previous);
        $this->query = $query;
    }

    /**
     * @param \Exception $e
     * @param string|null $adapterName
     * @param \Yuga\Database\Query\Exceptions\QueryBuilder\QueryObject|null $query
     * @return static|ColumnNotFoundException|ConnectionException|DuplicateColumnException|DuplicateEntryException|DuplicateKeyException|ForeignKeyException|NotNullException|TableNotFoundException
     *
     * @see https://dev.mysql.com/doc/refman/5.6/en/error-messages-server.html
     * @see https://www.postgresql.org/docs/9.4/static/errcodes-appendix.html
     * @see https://sqlite.org/c3ref/c_abort.html
     */
    public static function create(Exception $e, $adapterName = null, QueryObject $query = null)
    {
        
        if ($e instanceof PDOException) {
            /**
             * @var string|null $errorSqlState
             * @var integer|null $errorCode
             * @var string|null $errorMsg
             */
            list($errorIdentity, $errorCode, $errorMsg) = $e->errorInfo;

            $errorMsg = isset($errorMsg) ? $errorMsg : $e->getMessage();
            $errorCode = (int)(isset($errorCode) ? $errorCode : $e->getCode());
            
            switch ($adapterName) {
                case Mysql::class:
                    // https://dev.mysql.com/doc/refman/5.6/en/error-messages-server.html
                    switch ($errorCode) {
                        case 1062: // Message: Duplicate entry '%s' for key %d
                            return new DuplicateEntryException($errorMsg, $errorCode, $e->getPrevious(), $query);
                        case 1451: // Message: Cannot delete or update a parent row: a foreign key constraint fails (%s)
                        case 1452: // Message: Cannot add or update a child row: a foreign key constraint fails (%s)
                            return new ForeignKeyException($errorMsg, $errorCode, $e->getPrevious(), $query);
                        case 1048: // Column '%s' cannot be null
                            return new NotNullException($errorMsg, $errorCode, $e->getPrevious(), $query);
                        case 2013: // lost connection
                        case 2005: // unknown server host
                        case 1045: // access denied
                        case 1044: // access denied
                        case 2002: // failed to connect to server
                            return new ConnectionException($errorMsg, $errorCode, $e->getPrevious(), $query);
                        case 1146: // table doesn't exist
                            return new TableNotFoundException($errorMsg, $errorCode, $e->getPrevious(), $query);
                        case 1054: // unknown column
                            return new ColumnNotFoundException($errorMsg, $errorCode, $e->getPrevious(), $query);
                        case 1060: // Duplicate column name '%s'
                            return new DuplicateColumnException($errorMsg, $errorCode, $e->getPrevious(), $query);
                        case 1061: // Message: Duplicate key name '%s'
                            return new DuplicateKeyException($errorMsg, $errorCode, $e->getPrevious(), $query);
                    }
                    break;
                case Pgsql::class:
                    // https://www.postgresql.org/docs/9.4/static/errcodes-appendix.html
                    switch ($errorCode) {
                        case 42701: // exclusion_violation
                            return new DuplicateColumnException($e->getMessage(), $errorCode, $e->getPrevious(), $query);
                        case 23000: // foreign_key_violation
                        case 23503: // integrity_constraint_violation
                            return new ForeignKeyException($e->getMessage(), $errorCode, $e->getPrevious(), $query);
                        case 23505: // unique_violation
                            return new DuplicateEntryException($e->getMessage(), $errorCode, $e->getPrevious(), $query);
                        case 23502: // not_null_violation
                            return new NotNullException($e->getMessage(), $errorCode, $e->getPrevious(), $query);
                    }
                    break;
            }
        }

        return new static($e->getMessage(), (int)$e->getCode(), $e->getPrevious(), $query);
    }

    /**
     * Get query-object from last executed query.
     *
     * @return QueryObject|null
     */
    public function getQuery()
    {
        return $this->query?:null;
    }
}