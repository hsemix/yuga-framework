<?php

namespace Yuga\Views\Compilers\Concerns;

trait CompilesEchos
{
    protected function compileEchos(string $value): string
    {
        $value = preg_replace_callback(
            '/\{!!\s*(.+?)\s*!!\}/s',
            function ($matches) {
                return '<?= ' .
                    $this->compileEchoDefaults($matches[1]) .
                    ' ?>';
            },
            $value
        );

        return preg_replace_callback(
            '/\{\{\s*(.+?)\s*\}\}/s',
            function ($matches) {
                return '<?= htmlspecialchars((string)(' .
                    $this->compileEchoDefaults($matches[1]) .
                    '), ENT_QUOTES, "UTF-8") ?>';
            },
            $value
        );
    }

    protected function compileEchoDefaults(string $value): string
    {
        return preg_replace(
            '/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s',
            'isset($1) ? $1 : $2',
            $value
        );
    }
}