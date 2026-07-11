<?php

namespace Yuga\Views;

use InvalidArgumentException;

class Finder
{
    protected array $paths = [];

    protected array $namespaces = [];

    public function __construct(string|array $paths)
    {
        foreach ((array) $paths as $path) {
            $this->addLocation($path);
        }
    }

    public function addLocation(string $path): static
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        if (!in_array($path, $this->paths, true)) {
            $this->paths[] = $path;
        }

        return $this;
    }

    public function prependLocation(string $path): static
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        $this->paths = array_values(array_filter(
            $this->paths,
            fn(string $existing) => $existing !== $path
        ));

        array_unshift($this->paths, $path);

        return $this;
    }

    public function addNamespace(string $namespace, string|array $paths): static
    {
        $namespace = trim($namespace);

        if ($namespace === '') {
            throw new InvalidArgumentException('View namespace cannot be empty.');
        }

        foreach ((array) $paths as $path) {
            $path = rtrim($path, DIRECTORY_SEPARATOR);

            if (!isset($this->namespaces[$namespace])) {
                $this->namespaces[$namespace] = [];
            }

            if (!in_array($path, $this->namespaces[$namespace], true)) {
                $this->namespaces[$namespace][] = $path;
            }
        }

        return $this;
    }

    public function prependNamespace(string $namespace, string $path): static
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        $this->namespaces[$namespace] ??= [];

        $this->namespaces[$namespace] = array_values(array_filter(
            $this->namespaces[$namespace],
            fn(string $existing) => $existing !== $path
        ));

        array_unshift($this->namespaces[$namespace], $path);

        return $this;
    }

    public function find(string $view): string
    {
        if ($this->hasNamespace($view)) {
            return $this->findNamespacedView($view);
        }

        return $this->findInPaths($view, $this->paths);
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

    protected function hasNamespace(string $view): bool
    {
        return str_contains($view, '::');
    }

    protected function findNamespacedView(string $view): string
    {
        [$namespace, $name] = explode('::', $view, 2);

        if (!isset($this->namespaces[$namespace])) {
            throw new InvalidArgumentException(
                "View namespace [{$namespace}] is not registered."
            );
        }

        return $this->findInPaths($name, $this->namespaces[$namespace]);
    }

    protected function findInPaths(string $view, array $paths): string
    {
        $relativePath = str_replace(
            ['.', '/', '\\'],
            DIRECTORY_SEPARATOR,
            $view
        );

        foreach ($paths as $basePath) {
            foreach ($this->extensions() as $extension) {
                $path = $basePath
                    . DIRECTORY_SEPARATOR
                    . $relativePath
                    . $extension;

                if (is_file($path)) {
                    return $path;
                }
            }
        }

        throw new InvalidArgumentException("View [{$view}] not found.");
    }

    protected function extensions(): array
    {
        return [
            '.hax.php',
            '.php',
        ];
    }

    public function paths(): array
    {
        return $this->paths;
    }

    public function namespaces(): array
    {
        return $this->namespaces;
    }
}
