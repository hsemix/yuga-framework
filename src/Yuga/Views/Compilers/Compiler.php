<?php

namespace Yuga\Views\Compilers;

use Yuga\Views\Compilers\Concerns\CompilesComments;
use Yuga\Views\Compilers\Concerns\CompilesComponents;
use Yuga\Views\Compilers\Concerns\CompilesConditionals;
use Yuga\Views\Compilers\Concerns\CompilesEchos;
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

    protected array $compilers = [
        'comments',
        'components',
        'echos',
        'statements',
    ];

    public function __construct(
        protected ViewCache $cache
    ) {
    }

    public function compile(string $sourcePath): string
    {
        $compiledPath = $this->cache->path($sourcePath);

        if ($this->cache->expired($sourcePath, $compiledPath)) {
            file_put_contents(
                $compiledPath,
                $this->compileString(
                    file_get_contents($sourcePath)
                )
            );
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
}