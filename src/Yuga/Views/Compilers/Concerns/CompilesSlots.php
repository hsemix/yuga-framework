<?php

namespace Yuga\Views\Compilers\Concerns;

trait CompilesSlots
{
    protected function compileSlot(string $expression): string
    {
        return "<?php \$__sections->startSlot{$expression}; ?>";
    }

    protected function compileEndslot(): string
    {
        return "<?php \$__sections->stopSlot(); ?>";
    }

    protected function compileYieldSlot(string $expression): string
    {
        return "<?= \$__sections->yieldSlot{$expression}; ?>";
    }
}