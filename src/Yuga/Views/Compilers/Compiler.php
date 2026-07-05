<?php

namespace Yuga\Views\Compilers;

use Yuga\Views\Support\ViewCache;
use Yuga\Views\Compilers\Concerns\CompilesComments;
use Yuga\Views\Compilers\Concerns\CompilesConditionals;
use Yuga\Views\Compilers\Concerns\CompilesEchos;
use Yuga\Views\Compilers\Concerns\CompilesIncludes;
use Yuga\Views\Compilers\Concerns\CompilesInheritance;
use Yuga\Views\Compilers\Concerns\CompilesLoops;
use Yuga\Views\Compilers\Concerns\CompilesRawPhp;
use Yuga\Views\Compilers\Concerns\CompilesStatements;

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

    protected array $compilers = [
        'comments',
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