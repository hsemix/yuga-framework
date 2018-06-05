<?php
namespace Yuga\Database\ElegantManager;

use Yuga\Container\Container;
use Yuga\Database\Connection\Connection;
use Yuga\Database\Elegant\Model as Elegant;

class Manager
{
    protected $manager;
    private $container;
    private $connection;
    
    public function __construct(Container $container = null)
    {
        $this->setContainer($container ?: new Container);       
    }
    
    public function addConnection(Connection $connection)
    {
        $this->connection = $connection;
        $this->setManager($connection);
    }

    public function startElegant()
    {
        Elegant::setConnection($this->manager->getConnection());
    }

    private function setContainer(Container $container)
    {
        $this->container = $container;
    }

    private function setManager(Connection $connection)
    {
        $this->manager = $this->container->resolve(
            DatabaseManager::class,
            [$connection, $this->container]
        );
    }
}