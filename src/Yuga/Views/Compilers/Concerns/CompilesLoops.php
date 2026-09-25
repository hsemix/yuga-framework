<?php

namespace Yuga\Views\Compilers\Concerns;

trait CompilesLoops
{
    protected function compileForeach(string $expression): string
    {
        return "<?php foreach {$expression}: ?>";
    }

    protected function compileEndforeach(): string
    {
        return "<?php endforeach; ?>";
    }

    protected function compileFor(string $expression): string
    {
        return "<?php for {$expression}: ?>";
    }

    protected function compileEndfor(): string
    {
        return "<?php endfor; ?>";
    }

    protected function compileWhile(string $expression): string
    {
        return "<?php while {$expression}: ?>";
    }

    protected function compileEndwhile(): string
    {
        return "<?php endwhile; ?>";
    }
}