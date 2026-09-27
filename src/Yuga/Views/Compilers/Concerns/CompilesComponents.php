<?php

namespace Yuga\Views\Compilers\Concerns;

trait CompilesComponents
{
    protected function compileComponents(string $value): string
    {
        // Named slots first.
        $value = preg_replace_callback(
            '/<x-slot:([\w\-]+)>/',
            function ($matches) {
                $compiled = "<?php \$__engine->startComponentSlot('{$matches[1]}'); ?>";

                return $this->preserveSourceNewlines(
                    $matches[0],
                    $compiled
                );
            },
            $value
        );

        $value = preg_replace(
            '/<\/x-slot:[\w\-]+>/',
            '<?php $__engine->endComponentSlot(); ?>',
            $value
        );

        // Opening and self-closing component tags.
        $value = $this->compileComponentTags($value);

        // Closing component tags.
        $value = preg_replace_callback(
            '/<\/x-([\w\-\.:]+)>/',
            function ($matches) {
                if (str_starts_with($matches[1], 'slot:')) {
                    return $matches[0];
                }

                return '<?= $__engine->endComponent(); ?>';
            },
            $value
        );

        return $value;
    }

    protected function parseComponentAttributes(string $attributes): string
    {
        return $this->compileAttributes(
            $this->parseAttributes($attributes)
        );
    }

    protected function resolveComponentView(string $component): string
    {
        if (str_contains($component, '::')) {
            [$namespace, $name] = explode('::', $component, 2);

            return "{$namespace}::components.{$name}";
        }

        return "components.{$component}";
    }

    protected function compileComponentTags(string $value): string
    {
        $tags = $this->parseComponentTags($value);

        // Let's work backwards so replacing one tag doesn't invalidate
        // offsets belonging to tags later in the template.
        foreach (array_reverse($tags) as $tag) {
            $component = $this->resolveComponentView(
                $tag['name']
            );

            $attributes = $this->parseComponentAttributes(
                $tag['attributes']
            );

            if ($tag['selfClosing']) {
                $compiled =
                    "<?php \$__engine->startComponent(" .
                    "'{$component}', {$attributes}, __LINE__" .
                    "); echo \$__engine->endComponent(); ?>";
            } else {
                $compiled =
                    "<?php \$__engine->startComponent(" .
                    "'{$component}', {$attributes}, __LINE__" .
                    "); ?>";
            }

            $compiled = $this->preserveSourceNewlines(
                $tag['raw'],
                $compiled
            );

            $value =
                substr($value, 0, $tag['offset']) .
                $compiled .
                substr(
                    $value,
                    $tag['offset'] + $tag['length']
                );
        }

        return $value;
    }
}
