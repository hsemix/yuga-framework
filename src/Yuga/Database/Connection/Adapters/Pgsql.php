<?php
namespace Yuga\Database\Connection\Adapters;

use PDO;
use PDOException;
use Yuga\Database\Connection\Exception;
use Yuga\Database\Query\Exceptions\DatabaseQueryException;

class Pgsql extends BaseAdapter
{
    /**
     * @param array $config
     *
     * @return PDO
     * @throws \Yuga\Database\Query\Exceptions\TableNotFoundException
     * @throws \Yuga\Database\Query\Exceptions\ConnectionException
     * @throws \Yuga\Database\Query\Exceptions\ColumnNotFoundException
     * @throws \Yuga\Database\Connection\Exception
     * @throws \Yuga\Database\Query\Exceptions\DuplicateColumnException
     * @throws \Yuga\Database\Query\Exceptions\DuplicateEntryException
     * @throws \Yuga\Database\Query\Exceptions\DuplicateKeyException
     * @throws \Yuga\Database\Query\Exceptions\ForeignKeyException
     * @throws \Yuga\Database\Query\Exceptions\NotNullException
     */
    protected function doConnect($config)
    {
        if (extension_loaded('pdo_pgsql') === false) {
            throw new Exception(sprintf('%s library not loaded', 'pdo_pgsql'));
        }

        $connectionString = "pgsql:host={$config['host']};dbname={$config['database']}";

        if (isset($config['port']) === true) {
            $connectionString .= ";port={$config['port']}";
        }

        try {

            $connection = new PDO($connectionString, $config['username'], $config['password'], $config['options']);

            if (isset($config['charset']) === true) {
                $connection->prepare("SET NAMES '{$config['charset']}'")->execute();
            }

            if (isset($config['schema']) === true) {
                $connection->prepare("SET search_path TO '{$config['schema']}'")->execute();
            }

        } catch (PDOException $e) {
            throw DatabaseQueryException::create($e, $this->getQueryAdapterClass());
        }

        return $connection;
    }

    /**
     * Get query adapter class
     * @return string
     */
    public function getQueryAdapterClass()
    {
        return \Yuga\Database\Query\Grammar\Pgsql::class;
    }
}