<?php

declare(strict_types=1);

namespace Yuga\Database\ElegantManager;

use Yuga\Container\Container;
use Yuga\Database\Connection\Connection;

class DatabaseManager
{
    public function __construct(private readonly ?\Yuga\Database\Connection\Connection $connection = null, private readonly ?\Yuga\Container\Container $container = null)
    {
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
