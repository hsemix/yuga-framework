<?php

namespace Yuga\Views\Support;

class ViewCache
{
    public function __construct(
        protected string $cachePath
    ) {
        $this->cachePath = rtrim($cachePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }
    }

    public function path(string $viewPath): string
    {
        return $this->cachePath . md5($viewPath) . '.php';
    }

    public function expired(string $sourcePath, string $compiledPath): bool
    {
        if (!is_file($compiledPath)) {
            return true;
        }

        return filemtime($compiledPath) < filemtime($sourcePath);
    }
}