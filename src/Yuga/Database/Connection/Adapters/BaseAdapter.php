<?php

declare(strict_types=1);

namespace Yuga\Database\Connection\Adapters;

use Yuga\Container\Container;

abstract class BaseAdapter
{
    public function __construct(protected \Yuga\Container\Container $container)
    {
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
