<?php

namespace Yuga\Logger\Handlers;

use Yuga\Logger\Formatters\LineFormatter;

class DailyFileHandler implements HandlerInterface
{
    public function __construct(
        protected string $path,
        protected ?LineFormatter $formatter = null
    ) {
        $this->formatter ??= new LineFormatter();
    }

    public function handle(array $record): void
    {
        $file = rtrim($this->path, '/') . '/' . strtolower($record['channel']) . '-' . date('Y-m-d') . '.log';

        $dir = dirname($file);

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents(
            $file,
            $this->formatter->format($record) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}