<?php

namespace Yuga\Views\Compilers\Concerns;

trait CompilesIncludes
{
    protected function compileInclude(string $expression): string
    {
        return "<?= \$__engine->renderPartial{$this->appendDataToExpression($expression)}; ?>";
    }

    protected function appendDataToExpression(string $expression): string
    {
        $expression = trim($expression);

        if (str_starts_with($expression, '(') && str_ends_with($expression, ')')) {
            $expression = substr($expression, 1, -1);
        }

        return '(' . $expression . ', get_defined_vars())';
    }
}
