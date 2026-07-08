<?php

namespace Yuga\Views;

use Yuga\Support\Str;

class View
{
    protected array $data = [];
    protected ?string $fragmentName = null;

    public function __construct(
        protected Engine $engine,
        protected Finder $finder,
        protected string $view,
        array $data = []
    ) {
        $this->data = $data;
    }

    public function with(array|string $key, mixed $value = null): static
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    public function shares(string $key, mixed $value): static
    {
        return $this->with($key, $value);
    }

    public function first(?array $views = null): static
    {
        if (!$views) {
            return $this;
        }

        foreach ($views as $view) {
            if ($this->finder->exists($view)) {
                $this->view = $view;
                break;
            }
        }

        return $this;
    }

    public function fragment(string $name): static
    {
        $this->fragmentName = $name;

        return $this;
    }

    public function render(): string
    {
        if ($this->fragmentName) {
            return $this->engine->fragment(
                $this->view,
                $this->fragmentName,
                $this->data
            );
        }

        return $this->engine->render($this->view, $this->data);
    }

    public function asString(): string
    {
        return $this->render();
    }

    public function __toString(): string
    {
        try {
            return $this->render();
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public function __call(string $method, array $parameters): mixed
    {
        if (preg_match('/^with(.+)$/', $method, $matches)) {
            $decamelized = Str::deCamelize($matches[1]);
            $camelized = Str::camelize($decamelized);

            return $this->with($camelized, $parameters[0] ?? null);
        }

        throw new \BadMethodCallException("Method [{$method}] does not exist.");
    }
}
