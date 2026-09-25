<?php

namespace Yuga\Views\Compilers;

use Yuga\Views\Compilers\Concerns\CompilesComments;
use Yuga\Views\Compilers\Concerns\CompilesComponents;
use Yuga\Views\Compilers\Concerns\CompilesConditionals;
use Yuga\Views\Compilers\Concerns\CompilesEchos;
use Yuga\Views\Compilers\Concerns\CompilesFragments;
use Yuga\Views\Compilers\Concerns\CompilesHelpers;
use Yuga\Views\Compilers\Concerns\CompilesIncludes;
use Yuga\Views\Compilers\Concerns\CompilesInheritance;
use Yuga\Views\Compilers\Concerns\CompilesLoops;
use Yuga\Views\Compilers\Concerns\CompilesRawPhp;
use Yuga\Views\Compilers\Concerns\CompilesSlots;
use Yuga\Views\Compilers\Concerns\CompilesStacks;
use Yuga\Views\Compilers\Concerns\CompilesStatements;
use Yuga\Views\Support\ViewCache;

class Compiler
{
    use CompilesComments;
    use CompilesConditionals;
    use CompilesEchos;
    use CompilesIncludes;
    use CompilesInheritance;
    use CompilesLoops;
    use CompilesRawPhp;
    use CompilesStatements;
    use CompilesHelpers;
    use CompilesStacks;
    use CompilesSlots;
    use CompilesComponents;
    use CompilesFragments;

    protected array $compilers = [
        'comments',
        'fragments',
        'components',
        'echos',
        'statements',
    ];

    protected array $customDirectives = [];

    public function __construct(
        protected ViewCache $cache
    ) {}

    public function compile(string $sourcePath): string
    {
        $compiledPath = $this->cache->path($sourcePath);

        if ($this->cache->expired($sourcePath, $compiledPath)) {
            $compiled = $this->compileString(
                file_get_contents($sourcePath)
            );

            $compiled = "<?php /** YUGA_VIEW_PATH: {$sourcePath} */ ?>\n" . $compiled;

            file_put_contents($compiledPath, $compiled);
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

    public function directive(string $name, callable $handler): static
    {
        $this->customDirectives[$name] = $handler;

        return $this;
    }

    public function hasCustomDirective(string $name): bool
    {
        return isset($this->customDirectives[$name]);
    }

    public function compileCustomDirective(string $name, string $expression): string
    {
        return (string) call_user_func(
            $this->customDirectives[$name],
            $expression
        );
    }
}
