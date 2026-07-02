<?php

namespace Yuga\Logger\Formatters;

class LineFormatter
{
    public function format(array $record): string
    {
        $context = $this->formatContext($record['context'] ?? []);

        return sprintf(
            '[%s] %s.%s: %s%s',
            $record['time'],
            $record['channel'],
            $record['level'],
            $record['message'],
            $context ? ' ' . $context : ''
        );
    }

    protected function formatContext(array $context): string
    {
        if ($context === []) {
            return '';
        }

        return json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}