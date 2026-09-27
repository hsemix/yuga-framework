<?php

namespace Yuga\Views\Compilers\Concerns;

trait CompilesComponents
{
    protected function compileComponents(string $value): string
    {
        // Named component slots first
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

        // Self-closing component tags
        $value = preg_replace_callback(
            '/<x-([\w\-\.:]+)\s*(?<attributes>[^>]*)\/>/',
            function ($matches) {
                if (str_starts_with($matches[1], 'slot:')) {
                    return $matches[0];
                }

                $component = $this->resolveComponentView($matches[1]);

                $attributes = $this->parseComponentAttributes(
                    $matches['attributes'] ?? ''
                );

                $compiled = "<?php \$__engine->startComponent('{$component}', {$attributes}); echo \$__engine->endComponent(); ?>";

                return $this->preserveSourceNewlines(
                    $matches[0],
                    $compiled
                );
            },
            $value
        );

        // Opening component tags
        $value = preg_replace_callback(
            '/<x-([\w\-\.:]+)\s*(?<attributes>[^>]*)>/',
            function ($matches) {
                if (str_starts_with($matches[1], 'slot:')) {
                    return $matches[0];
                }

                $component = $this->resolveComponentView($matches[1]);

                $attributes = $this->parseComponentAttributes(
                    $matches['attributes'] ?? ''
                );

                $compiled = "<?php \$__engine->startComponent('{$component}', {$attributes}); ?>";

                return $this->preserveSourceNewlines(
                    $matches[0],
                    $compiled
                );
            },
            $value
        );

        // Closing component tags, but NOT x-slot tags
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
}
