<?php

namespace Yuga\Views;

class Factory
{
    public function __construct(
        protected Engine $engine,
        protected Finder $finder
    ) {}

    public function make(string $view, array $data = []): View
    {
        return new View($this->engine, $this->finder, $view, $data);
    }

    public function render(string $view, array $data = []): string
    {
        return $this->engine->render($view, $data);
    }

    public function exists(string $view): bool
    {
        return $this->finder->exists($view);
    }

    public function composer(array|string $views, callable|string $composer): static
    {
        $this->engine->composer($views, $composer);

        return $this;
    }

    public function addLocation(string $path): static
    {
        $this->finder->addLocation($path);

        return $this;
    }

    public function addNamespace(string $namespace, string|array $paths): static
    {
        $this->finder->addNamespace($namespace, $paths);

        return $this;
    }

    public function prependNamespace(string $namespace, string $path): static
    {
        $this->finder->prependNamespace($namespace, $path);

        return $this;
    }

    public function finder(): Finder
    {
        return $this->finder;
    }
}
