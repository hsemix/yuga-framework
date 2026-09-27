<?php

namespace Yuga\Views\Compilers\Concerns;

trait CompilesStatements
{
    protected function compileStatements(string $value): string
    {
        return preg_replace_callback(
            '/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x',
            function ($match) {
                $name = $match[1];
                $method = 'compile' . ucfirst($name);
                $expression = $match[3] ?? '';

                if ($this->hasCustomDirective($name)) {
                    $compiled = $this->compileCustomDirective(
                        $name,
                        $expression
                    );

                    return $this->preserveSourceNewlines(
                        $match[0],
                        $compiled
                    );
                }

                if (!method_exists($this, $method)) {
                    return $match[0];
                }

                $compiled = $this->{$method}($expression);

                return $this->preserveSourceNewlines(
                    $match[0],
                    $compiled
                );
            },
            $value
        );
    }
}
