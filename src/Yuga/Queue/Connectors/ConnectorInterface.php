<?php

namespace Yuga\Queue\Connectors;

interface ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @param array $config
     *
     * @return \Yuga\Queue\Connectors\QueueInterface
     */
    public function connect(array $config);
}
