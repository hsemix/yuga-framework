<?php

namespace Yuga\Views\Compilers\Concerns;

trait CompilesStatements
{
    protected function compileStatements(string $value): string
    {
        return preg_replace_callback(
            '/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x',
            function ($match) {

                $method = 'compile' . ucfirst($match[1]);

                if (!method_exists($this, $method)) {
                    return $match[0];
                }

                $expression = $match[3] ?? '';

                return $this->{$method}($expression);
            },
            $value
        );
    }
}