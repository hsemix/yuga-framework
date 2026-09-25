<?php

namespace Yuga\Views\Support;

class ComponentAttributeBag
{
    public function __construct(
        protected array $attributes = []
    ) {}

    public function all(): array
    {
        return $this->attributes;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function except(array|string $keys): static
    {
        $keys = (array) $keys;

        return new static(
            array_diff_key(
                $this->attributes,
                array_flip($keys)
            )
        );
    }

    public function only(array|string $keys): static
    {
        $keys = (array) $keys;

        return new static(
            array_intersect_key(
                $this->attributes,
                array_flip($keys)
            )
        );
    }

    public function merge(array $defaults = []): static
    {
        return new static(
            array_merge($defaults, $this->attributes)
        );
    }

    public function class(array|string $classes): static
    {
        $existing = $this->attributes['class'] ?? '';

        $classes = is_array($classes)
            ? AttributeBag::compileClass($classes)
            : $classes;

        $this->attributes['class'] = trim(
            $existing . ' ' . $classes
        );

        return $this;
    }

    public function __toString(): string
    {
        $html = [];

        foreach ($this->attributes as $key => $value) {

            if (is_bool($value)) {

                if ($value) {
                    $html[] = $key;
                }

                continue;
            }

            $html[] = sprintf(
                '%s="%s"',
                $key,
                htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')
            );
        }

        return implode(' ', $html);
    }
}
