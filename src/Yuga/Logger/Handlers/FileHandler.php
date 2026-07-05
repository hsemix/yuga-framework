<?php

namespace Yuga\Logger\Handlers;

class FileHandler implements HandlerInterface
{
    public function __construct(
        protected string $file,
        protected $formatter
    ) {
    }

    public function handle(array $record): void
    {
        $dir = dirname($this->file);

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents(
            $this->file,
            $this->formatter->format($record) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}