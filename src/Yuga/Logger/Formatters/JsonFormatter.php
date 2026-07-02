<?php

namespace Yuga\Logger\Formatters;

class JsonFormatter
{
    public function format(array $record): string
    {
        return json_encode(
            $record,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }
}