<?php
namespace Yuga\Database\Connection;

use PDO;
use Yuga\Container\Container;

class Connection
{
    protected $adapter;
    protected $container;
    protected $pdoInstance;
    protected $eventHandler;
    protected $adapterConfig;
    protected static $connection;
    public function __construct(array $adapterConfig, Container $container = null)
    {
        $this->adapterConfig = $adapterConfig;
        $this->container = $container ? $container : new Container;
        $this->setAdapter($adapterConfig['driver'])->setAdapterConfig($adapterConfig)->connect();
        $this->eventHandler = $this->container->resolve(EventHandler::class);
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
            $adapter = '\Yuga\Database\Connection\Adapters\\' . ucfirst(strtolower($this->adapter));
            $adapterInstance = $this->container->resolve($adapter, [$this->container]);
            $pdo = $adapterInstance->connect($this->adapterConfig);
            $this->setPdoInstance($pdo);
        } catch(PDOException $ex) {
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
}