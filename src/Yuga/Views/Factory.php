<?php

namespace Yuga\Views;

class Factory
{
    public function __construct(
        protected Engine $engine,
        protected Finder $finder
    ) {
    }

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
}