<?php

namespace Yuga\Database\Console\Backup;

use Exception;

class DatabaseBuilder
{
    protected $database;

    protected $console;

    public function __construct(Console $console)
    {
        $this->console = $console;
    }

    public function getDatabase(array $config)
    {
        $driver = $config['driver'];

        if ($driver == 'mysql') {
            return $this->buildMySQL($config);
        } elseif ($driver == 'sqlite') {
            return $this->buildSqlite($config);
        } elseif ($driver == 'pgsql') {
            return $this->buildPostgres($config);
        }

        throw new Exception('Database driver not supported yet.');
    }

    protected function buildMySQL(array $config)
    {
        $port = isset($config['port']) ? $config['port'] : 3306;

        return $this->database = new Databases\MySQLDatabase(
            $this->console,
            $config['database'],
            $config['username'],
            $config['password'],
            $config['host'],
            $port
        );
    }

    protected function buildSqlite(array $config)
    {
        return $this->database = new Databases\SqliteDatabase(
            $this->console,
            $config['database']
        );
    }

    protected function buildPostgres(array $config)
    {
        return $this->database = new Databases\PostgresDatabase(
            $this->console,
            $config['database'],
            $config['username'],
            $config['password'],
            $config['host']
        );
    }
}
