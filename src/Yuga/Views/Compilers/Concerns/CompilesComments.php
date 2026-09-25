<?php

namespace Yuga\Views\Compilers\Concerns;

trait CompilesComments
{
    protected function compileComments(string $value): string
    {
        return preg_replace(
            '/\{\{--((.|\s)*?)--\}\}/',
            '',
            $value
        );
    }
}