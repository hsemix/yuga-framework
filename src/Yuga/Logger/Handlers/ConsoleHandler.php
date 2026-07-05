<?php

namespace Yuga\Logger\Handlers;

class ConsoleHandler implements HandlerInterface
{
    public function __construct(
        protected $formatter
    ) {
    }

    public function handle(array $record): void
    {
        fwrite(STDERR, $this->formatter->format($record) . PHP_EOL);
    }
}