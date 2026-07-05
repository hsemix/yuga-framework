<?php

namespace Yuga\Views\Compilers\Concerns;

trait CompilesIncludes
{
    protected function compileInclude(string $expression): string
    {
        return "<?= \$__engine->renderPartial{$expression}; ?>";
    }
}