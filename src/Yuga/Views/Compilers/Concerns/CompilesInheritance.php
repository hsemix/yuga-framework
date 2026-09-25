<?php

namespace Yuga\Views\Compilers\Concerns;

trait CompilesInheritance
{
    protected function compileExtends(string $expression): string
    {
        return "<?php \$__sections->extend{$expression}; ?>";
    }

    protected function compileSection(string $expression): string
    {
        return "<?php \$__sections->start{$expression}; ?>";
    }

    protected function compileEndsection(): string
    {
        return "<?php \$__sections->stop(); ?>";
    }

    protected function compileYield(string $expression): string
    {
        return "<?= \$__sections->yield{$expression}; ?>";
    }

    protected function compileParent(): string
    {
        return "<?= \$__sections->parent(); ?>";
    }
}