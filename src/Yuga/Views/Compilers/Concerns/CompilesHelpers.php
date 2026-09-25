<?php

namespace Yuga\Views\Compilers\Concerns;

trait CompilesHelpers
{
    protected function compileJson(string $expression): string
    {
        return "<?= json_encode{$expression}; ?>";
    }

    protected function compileCsrf(): string
    {
        return '<?= csrf_field(); ?>';
    }

    protected function compileMethod(string $expression): string
    {
        return '<?= method_field' . $expression . '; ?>';
    }

    protected function compileChecked(string $expression): string
    {
        return "<?= {$this->stripParentheses($expression)} ? 'checked' : ''; ?>";
    }

    protected function compileSelected(string $expression): string
    {
        return "<?= {$this->stripParentheses($expression)} ? 'selected' : ''; ?>";
    }

    protected function compileDisabled(string $expression): string
    {
        return "<?= {$this->stripParentheses($expression)} ? 'disabled' : ''; ?>";
    }

    protected function compileClass(string $expression): string
    {
        return "<?= \\Yuga\\Views\\Support\\AttributeBag::compileClass{$expression}; ?>";
    }

    protected function compileStyle(string $expression): string
    {
        return "<?= \\Yuga\\Views\\Support\\AttributeBag::compileStyle{$expression}; ?>";
    }
}