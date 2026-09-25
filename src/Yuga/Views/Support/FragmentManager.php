<?php

namespace Yuga\Views\Support;

class FragmentManager
{
    protected array $fragments = [];

    protected array $stack = [];

    public function start(string $name): void
    {
        $this->stack[] = $name;
        ob_start();
    }

    public function stop(): string
    {
        $name = array_pop($this->stack);

        if (!$name) {
            throw new \RuntimeException('Cannot end a fragment that was not started.');
        }

        $content = ob_get_clean();

        $this->fragments[$name] = $content;

        return $content;
    }

    public function get(string $name): ?string
    {
        return $this->fragments[$name] ?? null;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->fragments);
    }

    public function all(): array
    {
        return $this->fragments;
    }

    public function flush(): void
    {
        $this->fragments = [];
        $this->stack = [];
    }
}
