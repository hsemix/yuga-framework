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
                return "<?php \$__engine->startComponentSlot('{$matches[1]}'); ?>";
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

                $component = str_replace(['.', ':'], '/', $matches[1]);

                $attributes = $this->parseComponentAttributes(
                    $matches['attributes'] ?? ''
                );

                return "<?php \$__engine->startComponent('components.{$component}', {$attributes}); echo \$__engine->endComponent(); ?>";
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

                $component = str_replace(['.', ':'], '/', $matches[1]);

                $attributes = $this->parseComponentAttributes(
                    $matches['attributes'] ?? ''
                );

                return "<?php \$__engine->startComponent('components.{$component}', {$attributes}); ?>";
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
        preg_match_all(
            '/([:@\w\-\.]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'))?/',
            $attributes,
            $matches,
            PREG_SET_ORDER
        );

        $compiled = [];

        foreach ($matches as $match) {
            $name = $match[1];

            $value = $match[2] ?? $match[3] ?? true;

            if (str_starts_with($name, ':')) {
                $name = substr($name, 1);

                $compiled[] = "'{$name}' => {$value}";
            } else {
                $compiled[] = $value === true
                    ? "'{$name}' => true"
                    : "'{$name}' => " . var_export($value, true);
            }
        }

        return '[' . implode(', ', $compiled) . ']';
    }

    protected function resolveComponentView(string $component): string
    {
        if (str_contains($component, '::')) {
            [$namespace, $name] = explode('::', $component, 2);

            $name = str_replace(
                ['.', ':'],
                '.',
                $name
            );

            return "{$namespace}::components.{$name}";
        }

        $component = str_replace(['.', ':'], '.', $component);

        return "components.{$component}";
    }
}
