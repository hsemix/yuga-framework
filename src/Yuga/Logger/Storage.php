<?php

namespace Yuga\Logger;

use Yuga\Interfaces\Logger\LoggerInterface;

class Storage implements LoggerInterface
{
    const APPEND = true;
    const OVERWRITE = false;

    public static function put($file, $message, $append = true)
    {
        $loggerFile = storage($file);

        $dir = dirname($loggerFile);

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents(
            $loggerFile,
            $message . PHP_EOL,
            $append ? FILE_APPEND | LOCK_EX : LOCK_EX
        );
    }

    public static function log($message, ?string $fileName = null)
    {
        $channel = $fileName ?: 'app';

        logger($channel)->info((string) $message);
    }
}