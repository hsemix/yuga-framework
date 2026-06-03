<?php

declare(strict_types=1);

namespace Yuga\Queue\Connectors;

interface ConnectorInterface
{
    /**
     * Establish a queue connection.
     *
     * @return \Yuga\Queue\Connectors\QueueInterface
     */
    public function connect(array $config);
}
