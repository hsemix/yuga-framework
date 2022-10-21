<?php

namespace Yuga\Database\Connection\Adapters;

use Yuga\Container\Container;

abstract class BaseAdapter
{
    protected $container;

    public function __construct(Container $container) // has been modified to default to null, wasn't before
    {
        $this->container = $container;
    }

    public function connect($config)
    {
        if (isset($config['options']) === false) {
            $config['options'] = [];
        }

        return $this->doConnect($config);
    }

    abstract protected function doConnect($config);
}
