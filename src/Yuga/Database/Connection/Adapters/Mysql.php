<?php

namespace Yuga\Database\Connection\Adapters;

use PDO;
use PDOException;
use Yuga\Database\Query\Exceptions\DatabaseQueryException;

class Mysql extends BaseAdapter
{
    protected function doConnect($config)
    {
        try {
            $connectionString = "mysql:dbname={$config['database']}";
            if (isset($config['host'])) {
                $connectionString .= ";host={$config['host']}";
            }
            if (isset($config['port'])) {
                $connectionString .= ";port={$config['port']}";
            }
            if (isset($config['unix_socket'])) {
                $connectionString .= ";unix_socket={$config['unix_socket']}";
            }
            $connection = $this->container->resolve(
                PDO::class,
                [$connectionString, $config['username'], $config['password'], $config['options']]
            );
            if (isset($config['charset'])) {
                $connection->prepare("SET NAMES '{$config['charset']}'")->execute();
            }
            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $connection;
        } catch (PDOException $e) {
            throw DatabaseQueryException::create($e, $this->getQueryAdapterClass());
        }
    }

    /**
     * Get query adapter class
     * @return string
     */
    public function getQueryAdapterClass()
    {
        return \Yuga\Database\Query\Grammar\Mysql::class;
    }
}