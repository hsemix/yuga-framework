<?php
namespace Yuga\Database\Connection;

use PDO;
use PDOException;
use Yuga\Container\Container;
use Yuga\Database\Query\QueryObject;

class Connection
{
    protected $adapter;
    protected \Yuga\Container\Container $container;
    protected $pdoInstance;
    protected $eventHandler;
    protected $adapterInstance;
    protected static $connection;
    /**
     * @var QueryObject|null
     */
    protected $lastQuery;
    public function __construct(protected array $adapterConfig, ?Container $container = null)
    {
        $this->container = $container instanceof \Yuga\Container\Container ? $container : new Container;
        $this->setAdapter($this->adapterConfig['driver'])->setAdapterConfig($this->adapterConfig)->connect();
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
            $adapter = '\Yuga\Database\Connection\Adapters\\' . ucfirst(strtolower((string) $this->adapter));
            $adapterInstance = $this->container->resolve($adapter, [$this->container]);
            $pdo = $adapterInstance->connect($this->adapterConfig);
            $this->setPdoInstance($pdo);
            $this->adapterInstance = $adapterInstance;
        } catch (PDOException $ex) {
            die("Database selection failed: ". $ex->getMessage());
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