<?php

namespace Yuga\Views\Compilers\Support;

class AttributeParser
{
    public function parse(string $attributes): array
    {
        $parsed = [];

        $length = strlen($attributes);
        $position = 0;

        while ($position < $length) {
            $whitespace = $this->consumeWhitespace(
                $attributes,
                $position
            );

            if ($position >= $length) {
                break;
            }

            $name = $this->consumeName(
                $attributes,
                $position
            );

            if ($name === '') {
                // Don't allow malformed input to trap the scanner.
                $position++;
                continue;
            }

            $this->consumeInlineWhitespace(
                $attributes,
                $position
            );

            $value = true;

            if (
                $position < $length &&
                $attributes[$position] === '='
            ) {
                $position++;

                $this->consumeInlineWhitespace(
                    $attributes,
                    $position
                );

                $value = $this->consumeValue(
                    $attributes,
                    $position,
                    $name
                );
            }

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

    protected function consumeWhitespace(string $input, int &$position): string
    {
        $start = $position;
        $length = strlen($input);

        while (
            $position < $length &&
            ctype_space($input[$position])
        ) {
            $position++;
        }

        return substr(
            $input,
            $start,
            $position - $start
        );
    }

    protected function consumeInlineWhitespace(
        string $input,
        int &$position
    ): void {
        $length = strlen($input);

        while (
            $position < $length &&
            (
                $input[$position] === ' ' ||
                $input[$position] === "\t"
            )
        ) {
            $position++;
        }
    }

    protected function consumeName(
        string $input,
        int &$position
    ): string {
        $start = $position;
        $length = strlen($input);

        while ($position < $length) {
            $char = $input[$position];

            if (
                ctype_alnum($char) ||
                $char === '_' ||
                $char === '-' ||
                $char === '.' ||
                $char === ':' ||
                $char === '@'
            ) {
                $position++;
                continue;
            }

            break;
        }

        return substr(
            $input,
            $start,
            $position - $start
        );
    }

    protected function consumeValue(
        string $input,
        int &$position,
        string $attribute
    ): string {
        $length = strlen($input);

        if ($position >= $length) {
            throw new \InvalidArgumentException(
                "Attribute [{$attribute}] is missing a value."
            );
        }

        $quote = $input[$position];

        if ($quote !== '"' && $quote !== "'") {
            throw new \InvalidArgumentException(
                "Attribute [{$attribute}] must use a quoted value."
            );
        }

        $position++;

        $value = '';
        $closed = false;

        while ($position < $length) {
            $char = $input[$position];

            if ($char === '\\') {
                if ($position + 1 < $length) {
                    $next = $input[$position + 1];

                    if (
                        $next === $quote ||
                        $next === '\\'
                    ) {
                        $value .= $next;
                        $position += 2;

                        continue;
                    }
                }

                $value .= $char;
                $position++;

                continue;
            }

            if ($char === $quote) {
                $position++;
                $closed = true;

                break;
            }

            $value .= $char;
            $position++;
        }

        if (!$closed) {
            throw new \InvalidArgumentException(
                "Attribute [{$attribute}] has an unterminated quoted value."
            );
        }

        return $value;
    }
}