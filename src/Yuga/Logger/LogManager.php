<?php

namespace Yuga\Logger;

use Yuga\Application\Application;
use Yuga\Logger\Handlers\DailyFileHandler;
use Yuga\Logger\Handlers\ConsoleHandler;
use Yuga\Logger\Formatters\LineFormatter;
use Yuga\Logger\Formatters\JsonFormatter;

class LogManager
{
    protected array $channels = [];

    public function __construct(
        protected Application $app
    ) {
    }

    public function channel(?string $name = null): Logger
    {
        $name ??= config('logging.default', 'app');

        if (!isset($this->channels[$name])) {
            $this->channels[$name] = $this->createChannel($name);
        }

        return $this->channels[$name];
    }

    protected function createChannel(string $name): Logger
    {
        $config = config("logging.channels.{$name}");

        if (!$config) {
            $config = config('logging.channels.app', [
                'driver' => 'daily',
                'path' => storage('logs'),
                'formatter' => 'line',
            ]);
        }

        $handlers = [];

        if (($config['driver'] ?? 'daily') === 'daily') {
            $handlers[] = new DailyFileHandler(
                $config['path'] ?? storage('logs'),
                $this->formatter($config['formatter'] ?? 'line')
            );
        }

        if (($config['driver'] ?? null) === 'console') {
            $handlers[] = new ConsoleHandler(
                $this->formatter($config['formatter'] ?? 'line')
            );
        }

        return new Logger($name, $handlers);
    }

    protected function formatter(string $name)
    {
        return $name === 'json'
            ? new JsonFormatter()
            : new LineFormatter();
    }

    public function __call($method, $parameters)
    {
        return $this->channel()->{$method}(...$parameters);
    }
}