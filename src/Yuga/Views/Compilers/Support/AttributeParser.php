<?php

namespace Yuga\Views\Compilers\Support;

class AttributeParser
{
    public function parse(string $attributes): array
    {
        preg_match_all(
            '/(\s*)([:@\w\-\.]+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'))?/',
            $attributes,
            $matches,
            PREG_SET_ORDER
        );

        $parsed = [];

        foreach ($matches as $match) {
            $whitespace = $match[1] ?? '';
            $name = $match[2];
            $value = $match[3] !== ''
                ? $match[3]
                : ($match[4] !== '' ? $match[4] : true);

            $bound = str_starts_with($name, ':');

            if ($bound) {
                $name = substr($name, 1);
            }

            $parsed[] = [
                'name' => $name,
                'value' => $value,
                'bound' => $bound,
                'whitespace' => $whitespace,
            ];
        }

        return $parsed;
    }

    public function compile(array $attributes): string
    {
        $compiled = '[';

        foreach ($attributes as $index => $attribute) {
            $name = $attribute['name'];
            $value = $attribute['value'];
            $bound = $attribute['bound'];
            $whitespace = $attribute['whitespace'] ?? '';

            // We care about source lines, not indentation.
            $newlines = substr_count($whitespace, "\n");

            if ($newlines > 0) {
                $compiled .= str_repeat("\n", $newlines);
            } elseif ($index > 0) {
                $compiled .= ' ';
            }

            $compiled .= var_export($name, true) . ' => ';

            if ($bound) {
                $compiled .= $value;
            } else {
                $compiled .= $value === true
                    ? 'true'
                    : var_export($value, true);
            }

            $compiled .= ',';
        }

        return $compiled . ']';
    }
}
