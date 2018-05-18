<?php
namespace Yuga\Database\Connection\Adapters;

use PDO;

class Mysql extends BaseAdapter
{
    protected function doConnect($config)
    {
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
        //$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $connection;
    }
}