<?php

namespace Yuga\Views\Compilers\Support;

class ComponentTagParser
{
    public function parse(string $value): array
    {
        $tags = [];
        $length = strlen($value);

        for ($i = 0; $i < $length; $i++) {
            if (
                $value[$i] !== '<' ||
                substr($value, $i, 3) !== '<x-'
            ) {
                continue;
            }

            // Closing tags are handled separately.
            if (substr($value, $i, 4) === '</x-') {
                continue;
            }

            $end = $this->findTagEnd($value, $i + 3);

            if ($end === null) {
                continue;
            }

            $raw = substr($value, $i, $end - $i + 1);

            if (!preg_match('/^<x-([\w\-\.:]+)/', $raw, $match)) {
                continue;
            }

            $name = $match[1];

            // x-slot:* has its own compiler logic.
            if (str_starts_with($name, 'slot:')) {
                continue;
            }

            $selfClosing = preg_match('/\/\s*>$/', $raw) === 1;

            $attributeStart = strlen($match[0]);

            $attributes = substr(
                $raw,
                $attributeStart,
                strlen($raw) - $attributeStart - 1
            );

            if ($selfClosing) {
                $attributes = preg_replace(
                    '/\/\s*$/',
                    '',
                    $attributes
                );
            }

            $tags[] = [
                'name' => $name,
                'attributes' => $attributes,
                'raw' => $raw,
                'offset' => $i,
                'length' => strlen($raw),
                'selfClosing' => $selfClosing,
            ];

            $i = $end;
        }

        return $tags;
    }

    protected function findTagEnd(
        string $value,
        int $start
    ): ?int {
        $length = strlen($value);
        $quote = null;
        $escaped = false;

        for ($i = $start; $i < $length; $i++) {
            $char = $value[$i];

            if ($escaped) {
                $escaped = false;
                continue;
            }

            if ($quote !== null) {
                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }

                if ($char === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
                continue;
            }

            if ($char === '>') {
                return $i;
            }
        }

        return null;
    }
}