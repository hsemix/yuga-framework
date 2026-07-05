<?php

namespace Yuga\Logger;

use Psr\Log\AbstractLogger;
use Stringable;

class Logger extends AbstractLogger
{
    public function __construct(
        protected string $channel = 'app',
        protected array $handlers = []
    ) {
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $record = [
            'time' => date('c'),
            'level' => strtoupper((string) $level),
            'channel' => $this->channel,
            'message' => $this->interpolate((string) $message, $context),
            'context' => $context,
            'memory' => memory_get_usage(true),
            'pid' => getmypid(),
        ];

        foreach ($this->handlers as $handler) {
            $handler->handle($record);
        }
    }

    protected function interpolate(string $message, array $context): string
    {
        $replace = [];

        foreach ($context as $key => $value) {
            if (is_scalar($value) || $value === null || $value instanceof Stringable) {
                $replace['{' . $key . '}'] = (string) $value;
            }
        }

        return strtr($message, $replace);
    }
}