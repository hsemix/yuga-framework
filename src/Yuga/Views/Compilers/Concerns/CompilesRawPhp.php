<?php

namespace Yuga\Views\Compilers\Concerns;

trait CompilesRawPhp
{
    protected function compilePhp(string $expression): string
    {
        return $expression
            ? "<?php {$this->stripParentheses($expression)}; ?>"
            : "<?php ";
    }

    protected function compileEndphp(): string
    {
        return " ?>";
    }

    protected function stripParentheses(string $expression): string
    {
        $expression = trim($expression);

        if (
            str_starts_with($expression, '(') &&
            str_ends_with($expression, ')')
        ) {
            return substr($expression, 1, -1);
        }

        return $expression;
    }
}