<?php

namespace Yuga\Interfaces\Logger;

interface LoggerInterface
{
    public static function put($file, $message, $append = true);
}