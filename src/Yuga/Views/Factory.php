<?php

namespace Yuga\Views;

class Factory
{
    protected array $shared = [];

    public function __construct(
        protected Engine $engine,
        protected Finder $finder
    ) {}

    public function make(string $view, array $data = []): View
    {
        $data = array_merge($this->shared, $data);
        return new View($this->engine, $this->finder, $view, $data);
    }

    public function share(array|string $key, mixed $value = null): static
    {
        if (is_array($key)) {
            $this->shared = array_merge($this->shared, $key);
        } else {
            $this->shared[$key] = $value;
        }

        return $this;
    }

    public function shared(): array
    {
        return $this->shared;
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
