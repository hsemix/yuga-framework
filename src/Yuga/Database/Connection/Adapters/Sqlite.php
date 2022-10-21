<?php

namespace Yuga\Database\Connection\Adapters;

use PDO;
use Yuga\Database\Connection\Exception;

class Sqlite extends BaseAdapter
{
    public function doConnect($config)
    {
        if (extension_loaded('pdo_sqlite') === false) {
            throw new Exception(sprintf('%s library not loaded', 'pdo_sqlite'));
        }

        $path = storage('database');
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $connectionString = 'sqlite:'.$path.DIRECTORY_SEPARATOR.$config['database'];

        $connection = $this->container->resolve(
            PDO::class,
            [$connectionString, null, null, $config['options']]
        );
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $connection;
    }

    /**
     * Get query adapter class.
     *
     * @return string
     */
    public function getQueryAdapterClass()
    {
        return \Yuga\Database\Query\Grammar\Sqlite::class;
    }
}
