<?php

namespace Yuga\Views;

use InvalidArgumentException;

class Finder
{
    public function __construct(
        protected string $basePath
    ) {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    public function find(string $view): string
    {
        $view = str_replace('.', DIRECTORY_SEPARATOR, $view);

        foreach (['.hax.php', '.php'] as $extension) {
            $path = $this->basePath . $view . $extension;

            if (is_file($path)) {
                return $path;
            }
        }

        throw new InvalidArgumentException("View [{$view}] not found.");
    }

    public function exists(string $view): bool
    {
        try {
            $this->find($view);
            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    public function basePath(): string
    {
        return $this->basePath;
    }
}