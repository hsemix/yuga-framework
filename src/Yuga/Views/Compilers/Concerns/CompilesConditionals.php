<?php

namespace Yuga\Views\Compilers\Concerns;

trait CompilesConditionals
{
    protected function compileIf(string $expression): string
    {
        return "<?php if {$expression}: ?>";
    }

    protected function compileElseif(string $expression): string
    {
        return "<?php elseif {$expression}: ?>";
    }

    protected function compileElse(): string
    {
        return "<?php else: ?>";
    }

    protected function compileEndif(): string
    {
        return "<?php endif; ?>";
    }
}