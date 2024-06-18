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
        
        $message = $message . "\r\n";

        if ($append)
            file_put_contents($loggerFile, $message, FILE_APPEND);
        else 
            file_put_contents($loggerFile, $message);
    }

    /**
     * Log messages
     * 
     * @author <semix.hamidouh@gmail.com>
     * 
     * @return void
     */
    public static function log($message, ?string $fileName = null)
    {
        if (!$fileName)
            $fileName = 'log-' . date('Y-m-d');
        $outf = fopen(storage() . "logs/{$fileName}.log", "a");
        fwrite($outf, date("c") . ": $message\n\n");
        fclose($outf);
    }
}