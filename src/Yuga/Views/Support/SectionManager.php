<?php

namespace Yuga\Views\Support;

class SectionManager
{
    protected array $sections = [];

    protected array $sectionStack = [];

    protected ?string $layout = null;

    protected string $parentPlaceholder = '##YUGA_PARENT_PLACEHOLDER##';

    protected array $pushes = [];

    protected array $prepends = [];

    protected array $pushStack = [];

    protected array $renderedOnce = [];

    protected array $slots = [];

    protected array $slotStack = [];

    public function start(string $name, ?string $value = null): void
    {
        if ($value !== null) {
            $this->sections[$name] = $value;
            return;
        }

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

        $this->pushes = [];
        $this->prepends = [];
        $this->pushStack = [];
        $this->renderedOnce = [];

        $this->slots = [];
        $this->slotStack = [];
    }

    public function startPush(string $name): void
    {
        $this->pushStack[] = ['type' => 'push', 'name' => $name];
        ob_start();
    }

    public function startPrepend(string $name): void
    {
        $this->pushStack[] = ['type' => 'prepend', 'name' => $name];
        ob_start();
    }

    public function stopPush(): void
    {
        $last = array_pop($this->pushStack);

        if (!$last) {
            throw new \RuntimeException('Cannot end a push/prepend that was not started.');
        }

        $content = ob_get_clean();

        if ($last['type'] === 'prepend') {
            $this->prepends[$last['name']][] = $content;
        } else {
            $this->pushes[$last['name']][] = $content;
        }
    }

    public function yieldPushContent(string $name): string
    {
        $output = '';

        if (isset($this->prepends[$name])) {
            $output .= implode('', array_reverse($this->prepends[$name]));
        }

        if (isset($this->pushes[$name])) {
            $output .= implode('', $this->pushes[$name]);
        }

        return $output;
    }

    public function hasRenderedOnce(string $id): bool
    {
        return isset($this->renderedOnce[$id]);
    }

    public function markAsRenderedOnce(string $id): void
    {
        $this->renderedOnce[$id] = true;
    }

    public function startSlot(string $name): void
    {
        $this->slotStack[] = $name;

        ob_start();
    }

    public function stopSlot(): void
    {
        $name = array_pop($this->slotStack);

        if (!$name) {
            throw new \RuntimeException('Cannot end a slot that was not started.');
        }

        $this->slots[$name] = ob_get_clean();
    }

    public function yieldSlot(string $name, string $default = ''): string
    {
        return $this->slots[$name] ?? $default;
    }
}
