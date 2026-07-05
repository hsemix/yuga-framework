<?php

namespace Yuga\Views\Support;

class SectionManager
{
    protected array $sections = [];

    protected array $sectionStack = [];

    protected ?string $layout = null;

    protected string $parentPlaceholder = '##YUGA_PARENT_PLACEHOLDER##';

    public function start(string $name): void
    {
        $this->sectionStack[] = $name;
        ob_start();
    }

    public function stop(): void
    {
        $name = array_pop($this->sectionStack);

        if (!$name) {
            throw new \RuntimeException('Cannot end a section that was not started.');
        }

        $content = ob_get_clean();

        // Child section already exists.
        // This means the layout is now defining the parent/default section.
        if (isset($this->sections[$name])) {
            $childContent = $this->sections[$name];

            if (str_contains($childContent, $this->parentPlaceholder)) {
                $this->sections[$name] = str_replace(
                    $this->parentPlaceholder,
                    $content,
                    $childContent
                );
            }

            // If child does not contain @parent, child overrides parent.
            return;
        }

        $this->sections[$name] = $content;
    }

    public function parent(): string
    {
        return $this->parentPlaceholder;
    }

    public function yield(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    public function extend(string $layout): void
    {
        $this->layout = $layout;
    }

    public function layout(): ?string
    {
        return $this->layout;
    }

    public function flush(): void
    {
        $this->sections = [];
        $this->sectionStack = [];
        $this->layout = null;
    }
}
