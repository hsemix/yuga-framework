<?php

namespace Yuga\Database\Connection;

use PDO;
use PDOException;
use Yuga\Container\Container;
use Yuga\Database\Query\QueryObject;

class Connection
{
    protected $adapter;
    protected $container;
    protected $pdoInstance;
    protected $eventHandler;
    protected $adapterConfig;
    protected $adapterInstance;
    protected static $connection;
    /**
     * @var QueryObject|null
     */
    protected $lastQuery;

    public function __construct(array $adapterConfig, Container $container = null)
    {
        $this->adapterConfig = $adapterConfig;
        $this->container = $container ? $container : new Container();
        $this->setAdapter($adapterConfig['driver'])->setAdapterConfig($adapterConfig)->connect();
        $this->eventHandler = $this->container->resolve(EventHandler::class);
    }

    public function getAdapterInstance()
    {
        return $this->adapterInstance;
    }

    public function setAdapter($adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }

    public function setAdapterConfig(array $adapterConfig)
    {
        $this->adapterConfig = $adapterConfig;

        return $this;
    }

    protected function connect()
    {
        try {
            // Build a database connection if we don't have one connected
            $adapter = '\Yuga\Database\Connection\Adapters\\'.ucfirst(strtolower($this->adapter));
            $adapterInstance = $this->container->resolve($adapter, [$this->container]);
            $pdo = $adapterInstance->connect($this->adapterConfig);
            $this->setPdoInstance($pdo);
            $this->adapterInstance = $adapterInstance;
        } catch (PDOException $ex) {
            exit('Database selection failed: '.$ex->getMessage());
        }

        // Preserve the first database connection with a static property
        if (!static::$connection) {
            static::$connection = $this;
        }
    }

    public function setPdoInstance(PDO $pdo)
    {
        $this->pdoInstance = $pdo;

        return $this;
    }

    public function getContainer()
    {
        return $this->container;
    }

    public static function getStoredConnection()
    {
        return static::$connection;
    }

    public function getPdoInstance()
    {
        return $this->pdoInstance;
    }

    public function getAdapter()
    {
        return $this->adapter;
    }

    public function getAdapterConfig()
    {
        return $this->adapterConfig;
    }

    /**
     * @return EventHandler
     */
    public function getEventHandler()
    {
        return $this->eventHandler;
    }

    /**
     * Set query-object for last executed query.
     *
     * @param QueryObject $query
     *
     * @return static
     */
    public function setLastQuery(QueryObject $query)
    {
        $this->lastQuery = $query;

        return $this;
    }

    /**
     * Get query-object from last executed query.
     *
     * @return QueryObject|null
     */
    public function getLastQuery()
    {
        return $this->lastQuery ?: null;
    }
}
