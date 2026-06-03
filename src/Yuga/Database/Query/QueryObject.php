<?php

namespace Yuga\Database\Query;

use PDO;
use Yuga\Database\Connection\Connection;

class QueryObject
{

    protected string $sql;
    public function __construct($sql, protected array $bindings, protected ?\Yuga\Database\Connection\Connection $connection = null)
    {
        $this->sql = (string)$sql;
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
            $keys[] = is_string($key) ? '/:' . $key . '/' : '/[?]/';
            if (is_string($value)) {
                $values[$key] = $this->connection->getPdoInstance()->quote($value);
            }
            if (is_array($value)) {
                $values[$key] = implode(',', $this->connection->getPdoInstance()->quote($value));
            }
            if ($value === null) {
                $values[$key] = 'NULL';
            }
        }
        return preg_replace($keys, $values, (string) $query, 1, $count);
    }
}
