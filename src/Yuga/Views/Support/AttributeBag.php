<?php

namespace Yuga\Views\Support;

class AttributeBag
{
    public static function compileClass(array $classes): string
    {
        $compiled = [];

        foreach ($classes as $class => $condition) {

            if (is_numeric($class)) {
                $compiled[] = $condition;
                continue;
            }

            if ($condition) {
                $compiled[] = $class;
            }
        }

        return implode(' ', $compiled);
    }

    public static function compileStyle(array $styles): string
    {
        $compiled = [];

        foreach ($styles as $style => $condition) {

            if (is_numeric($style)) {
                $compiled[] = rtrim($condition, ';');
                continue;
            }

            if ($condition) {
                $compiled[] = rtrim($style, ';');
            }
        }

        return implode('; ', $compiled);
    }
}
