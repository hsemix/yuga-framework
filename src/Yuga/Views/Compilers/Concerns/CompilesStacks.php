<?php

namespace Yuga\Views\Compilers\Concerns;

trait CompilesStacks
{
    protected function compilePush(string $expression): string
    {
        return "<?php \$__sections->startPush{$expression}; ?>";
    }

    protected function compileEndpush(): string
    {
        return "<?php \$__sections->stopPush(); ?>";
    }

    protected function compilePrepend(string $expression): string
    {
        return "<?php \$__sections->startPrepend{$expression}; ?>";
    }

    protected function compileEndprepend(): string
    {
        return "<?php \$__sections->stopPush(); ?>";
    }

    protected function compileStack(string $expression): string
    {
        return "<?= \$__sections->yieldPushContent{$expression}; ?>";
    }

    protected function compileOnce(string $expression): string
    {
        $id = trim($this->stripParentheses($expression));

        if ($id === '') {
            $id = "'" . md5(uniqid('', true)) . "'";
        }

        return "<?php if (!\$__sections->hasRenderedOnce({$id})): \$__sections->markAsRenderedOnce({$id}); ?>";
    }

    protected function compileEndonce(): string
    {
        return "<?php endif; ?>";
    }
}