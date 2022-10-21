<?php

namespace Yuga\Database\Query;

use Yuga\Database\Connection\Connection;

class QueryObject
{
    protected $sql;
    protected $bindings = [];
    protected $connection;

    public function __construct($sql, array $bindings, Connection $connection = null)
    {
        $this->sql = (string) $sql;
        $this->bindings = $bindings;
        $this->connection = $connection;
    }

    public function getSql()
    {
        return $this->sql;
    }

    public function getBindings()
    {
        return $this->bindings;
    }

    public function getRawSql()
    {
        return $this->interpolateQuery($this->sql, $this->bindings);
    }

    protected function interpolateQuery($query, $params)
    {
        $keys = [];
        $values = $params;
        // build a regular expression for each parameter
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/:'.$key.'/';
            } else {
                $keys[] = '/[?]/';
            }
            if (is_string($value)) {
                $values[$key] = $this->connection->getPdoInstance()->quote($value);
            }
            if (is_array($value)) {
                $values[$key] = join(',', $this->connection->getPdoInstance()->quote($value));
            }
            if ($value === null) {
                $values[$key] = 'NULL';
            }
        }

        return preg_replace($keys, $values, $query, 1, $count);
    }
}
