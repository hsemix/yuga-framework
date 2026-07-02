<?php

namespace Yuga\Logger;

use Yuga\Logger\Handlers\DailyFileHandler;

class LogManager
{
    protected array $channels = [];

    public function channel(string $name = 'app'): Logger
    {
        if (!isset($this->channels[$name])) {
            $this->channels[$name] = new Logger($name, [
                new DailyFileHandler(storage('logs')),
            ]);
        }

        return $this->channels[$name];
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->channel()->emergency($message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->channel()->alert($message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->channel()->critical($message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->channel()->error($message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->channel()->warning($message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->channel()->notice($message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->channel()->info($message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->channel()->debug($message, $context);
    }
}