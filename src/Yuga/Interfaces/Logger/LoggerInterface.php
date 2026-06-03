<?php

declare(strict_types=1);

namespace Yuga\Interfaces\Logger;

interface LoggerInterface
{
    public static function put($file, $message, $append = true);
}
