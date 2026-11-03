<?php

declare(strict_types=1);

namespace Yuga\Queue;

use Closure;
use InvalidArgumentException;
use Yuga\Queue\Contracts\QueuePublisherInterface;
use Yuga\Queue\Contracts\QueueTransportInterface;
use Yuga\Queue\Exceptions\TransportException;
use Yuga\Queue\Exceptions\UnsupportedCapabilityException;

final class QueueManager
{
    /** @var array<string, Closure> */
    private array $factories = [];

    /** @var array<string, QueuePublisherInterface> */
    private array $connections = [];

    public function __construct(private readonly array $config) {}

    public function extend(string $driver, callable $factory): self
    {
        if (trim($driver) === '') {
            throw new InvalidArgumentException(
                'Queue driver name must not be empty.'
            );
        }

        if (isset($this->factories[$driver])) {
            throw new InvalidArgumentException(
                "Queue driver [{$driver}] is already registered."
            );
        }

        $this->factories[$driver] = Closure::fromCallable($factory);

        return $this;
    }

    public function connection(
        ?string $name = null
    ): QueuePublisherInterface {
        $name ??= $this->config['default'] ?? null;

        if (!is_string($name) || trim($name) === '') {
            throw new InvalidArgumentException(
                'A default queue connection must be configured.'
            );
        }

        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        $settings = $this->config['connections'][$name] ?? null;

        if (!is_array($settings)) {
            throw new InvalidArgumentException(
                "Queue connection [{$name}] is not configured."
            );
        }

        $driver = $settings['driver'] ?? null;

        if (!is_string($driver) || trim($driver) === '') {
            throw new InvalidArgumentException(
                "Queue connection [{$name}] requires a driver."
            );
        }

        $factory = $this->factories[$driver] ?? null;

        if ($factory === null) {
            throw new InvalidArgumentException(
                "Queue driver [{$driver}] is not registered."
            );
        }

        $publisher = $factory($settings);

        if (!$publisher instanceof QueuePublisherInterface) {
            throw new TransportException(
                "Factory for [{$driver}] must return a QueuePublisherInterface."
            );
        }

        return $this->connections[$name] = $publisher;
    }

    public function transport(
        ?string $name = null
    ): QueueTransportInterface {
        $connection = $this->connection($name);

        if (!$connection instanceof QueueTransportInterface) {
            throw new UnsupportedCapabilityException(
                'This connection does not support polling through queue:work. '
                    . 'Use its dedicated consumer.'
            );
        }

        return $connection;
    }
}
