<?php

namespace Yuga\Views\Compilers\Concerns;

trait CompilesFragments
{
    protected function compileFragments(string $value): string
    {
        $value = preg_replace_callback(
            '/<fragment\s+name=["\']([^"\']+)["\']\s*>/',
            function ($matches) {
                $compiled = "<?php \$__engine->startFragment('{$matches[1]}'); ?>";

                return $this->preserveSourceNewlines(
                    $matches[0],
                    $compiled
                );
            },
            $value
        );

        return preg_replace(
            '/<\/fragment>/',
            '<?= $__engine->endFragment(); ?>',
            $value
        );
    }
}
