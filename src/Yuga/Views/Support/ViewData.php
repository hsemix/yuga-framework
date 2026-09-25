<?php

namespace Yuga\Views\Support;

class ViewData
{
    public function __construct(
        protected string $name,
        protected array $data = []
    ) {}

    public function name(): string
    {
        return $this->name;
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

    public function data(): array
    {
        return $this->data;
    }
}
