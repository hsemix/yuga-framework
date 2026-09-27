<?php

namespace Yuga\Views\Compilers\Concerns;

trait CompilesEchos
{
    protected function compileEchos(string $value): string
    {
        // Raw echos
        $value = preg_replace_callback(
            '/\{!!(.+?)!!\}/s',
            function ($matches) {
                return '<?= ' .
                    $this->compileEchoDefaults($matches[1]) .
                    ' ?>';
            },
            $value
        );

        // Escaped echos
        return preg_replace_callback(
            '/\{\{(.+?)\}\}/s',
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
            '/^(\s*)(?=\$)(.+?)(?:\s+or\s+)(.+?)(\s*)$/s',
            '$1isset($2) ? $2 : $3$4',
            $value
        );
    }
}