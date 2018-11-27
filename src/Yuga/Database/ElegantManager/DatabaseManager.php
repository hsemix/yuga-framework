<?php
namespace Yuga\Database\ElegantManager;

use Yuga\Container\Container;
use Yuga\Database\Connection\Connection;

class DatabaseManager
{
    private $container;
    private $connection;
    
    public function __construct(Connection $connection = null, Container $container = null)
    {
        $this->connection = $connection;
        $this->container = $container;
    }

    public function getConnection()
    {
        return $this->connection;
    }
    public function getContainer()
    {
        return $this->container;
    }
}