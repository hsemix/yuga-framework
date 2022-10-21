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

        $message = $message."\r\n";

        if ($append) {
            file_put_contents($loggerFile, $message, FILE_APPEND);
        } else {
            file_put_contents($loggerFile, $message);
        }
    }
}
