<?php

namespace Yuga\Queue\Connectors;

use Yuga\Queue\Queues\WaitQueue;


class WaitConnector implements ConnectorInterface
{

    /**
     * Establish a queue connection.
     *
     * @param  array  $config
     * @return \Yuga\Queue\Contracts\QueueInterface
     */
    public function connect(array $config)
    {
        return new WaitQueue;
    }

}
