<?php

namespace Yuga\Views\Compilers\Concerns;

trait CompilesComments
{
    protected function compileComments(string $value): string
    {
        return preg_replace_callback(
            '/\{\{--(.*?)--\}\}/s',
            function ($matches) {
                return str_repeat(
                    "\n",
                    substr_count($matches[0], "\n")
                );
            },
            $value
        );
    }
}
