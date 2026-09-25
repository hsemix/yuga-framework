<?php

namespace Yuga\Views\Compilers\Support;

class AttributeParser
{
    public function parse(string $attributes): array
    {
        preg_match_all(
            '/([:@\w\-\.]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'))?/',
            $attributes,
            $matches,
            PREG_SET_ORDER
        );

        $parsed = [];

        foreach ($matches as $match) {
            $name = $match[1];
            $value = $match[2] ?? $match[3] ?? true;
            $bound = str_starts_with($name, ':');

            if ($bound) {
                $name = substr($name, 1);
            }

            $parsed[] = [
                'name' => $name,
                'value' => $value,
                'bound' => $bound,
            ];
        }

        return $parsed;
    }

    public function compile(array $attributes): string
    {
        $compiled = [];

        foreach ($attributes as $attribute) {
            $name = $attribute['name'];
            $value = $attribute['value'];
            $bound = $attribute['bound'];

            if ($bound) {
                $compiled[] = var_export($name, true)
                    . ' => '
                    . $value;

                continue;
            }

            $compiled[] = $value === true
                ? var_export($name, true) . ' => true'
                : var_export($name, true)
                    . ' => '
                    . var_export($value, true);
        }

        return '[' . implode(', ', $compiled) . ']';
    }
}