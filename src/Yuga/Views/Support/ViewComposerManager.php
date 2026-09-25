<?php

namespace Yuga\Views\Support;

class ViewComposerManager
{
    protected array $composers = [];

    public function composer(array|string $views, callable|string $composer): static
    {
        foreach ((array) $views as $view) {
            $this->composers[$view][] = $composer;
        }

        return $this;
    }

    public function compose(string $view, array $data = []): array
    {
        $viewData = new ViewData($view, $data);

        foreach ($this->matchingComposers($view) as $composer) {
            $this->callComposer($composer, $viewData);
        }

        return $viewData->data();
    }

    protected function matchingComposers(string $view): array
    {
        $matched = [];

        foreach ($this->composers as $pattern => $composers) {
            if ($this->matches($pattern, $view)) {
                $matched = array_merge($matched, $composers);
            }
        }

        return $matched;
    }

    protected function matches(string $pattern, string $view): bool
    {
        if ($pattern === $view || $pattern === '*') {
            return true;
        }

        $pattern = str_replace('\*', '.*', preg_quote($pattern, '/'));

        return (bool) preg_match('/^' . $pattern . '$/', $view);
    }

    protected function callComposer(callable|string $composer, ViewData $view): void
    {
        if (is_string($composer) && class_exists($composer)) {
            $instance = new $composer();

            if (!method_exists($instance, 'compose')) {
                throw new \RuntimeException("View composer [{$composer}] must have a compose method.");
            }

            $instance->compose($view);

            return;
        }

        if (is_callable($composer)) {
            $composer($view);
            return;
        }

        throw new \RuntimeException('Invalid view composer.');
    }
}
