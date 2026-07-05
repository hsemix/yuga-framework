<?php

namespace Yuga\Views;

use Yuga\Views\Support\ViewCache;

class Compiler
{
    protected array $compilers = [
        'comments',
        'echos',
        'statements',
    ];

    protected array $contentTags = ['{{', '}}'];

    protected array $escapedTags = ['{!!', '!!}'];

    public function __construct(
        protected ViewCache $cache
    ) {
    }

    public function compile(string $sourcePath): string
    {
        $compiledPath = $this->cache->path($sourcePath);

        if ($this->cache->expired($sourcePath, $compiledPath)) {
            file_put_contents($compiledPath, $this->compileString(file_get_contents($sourcePath)));
        }

        return $compiledPath;
    }

    public function compileString(string $value): string
    {
        foreach ($this->compilers as $compiler) {
            $method = 'compile' . ucfirst($compiler);
            $value = $this->{$method}($value);
        }

        return $value;
    }

    protected function compileComments(string $value): string
    {
        return preg_replace('/\{\{--((.|\s)*?)--\}\}/', '', $value);
    }

    protected function compileEchos(string $value): string
    {
        $value = preg_replace_callback('/\{!!\s*(.+?)\s*!!\}/s', function ($matches) {
            return '<?= ' . $this->compileEchoDefaults($matches[1]) . ' ?>';
        }, $value);

        return preg_replace_callback('/\{\{\s*(.+?)\s*\}\}/s', function ($matches) {
            return '<?= htmlspecialchars((string)(' . $this->compileEchoDefaults($matches[1]) . '), ENT_QUOTES, "UTF-8") ?>';
        }, $value);
    }

    protected function compileStatements(string $value): string
    {
        return preg_replace_callback('/\B@(\w+)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?/x', function ($match) {
            $method = 'compile' . ucfirst($match[1]);

            if (!method_exists($this, $method)) {
                return $match[0];
            }

            $expression = $match[3] ?? '';

            return $this->{$method}($expression);
        }, $value);
    }

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

    protected function compileInclude(string $expression): string
    {
        return "<?= \$__engine->render{$expression}; ?>";
    }

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

    protected function compileParent(): string
    {
        return "<?= \$__sections->parent(); ?>";
    }

    protected function compileEchoDefaults(string $value): string
    {
        return preg_replace('/^(?=\$)(.+?)(?:\s+or\s+)(.+?)$/s', 'isset($1) ? $1 : $2', $value);
    }

    protected function stripParentheses(string $expression): string
    {
        $expression = trim($expression);

        if (str_starts_with($expression, '(') && str_ends_with($expression, ')')) {
            return substr($expression, 1, -1);
        }

        return $expression;
    }
}