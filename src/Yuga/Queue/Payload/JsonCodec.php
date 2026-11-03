<?php

declare(strict_types=1);

namespace Yuga\Queue\Payload;

use InvalidArgumentException;

final class JsonCodec implements PayloadCodecInterface
{
    public function name(): string
    {
        return 'json';
    }

    public function encode(mixed $value): string
    {
        $this->validate($value);

        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    public function decode(string $payload): mixed
    {
        return json_decode(
            $payload,
            associative: true,
            flags: JSON_THROW_ON_ERROR
        );
    }

    private function validate(mixed $value, int $depth = 0): void
    {
        if ($depth > 128) {
            throw new InvalidArgumentException(
                'JSON job data exceeds the maximum nesting depth.'
            );
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                $this->validate($item, $depth + 1);
            }

            return;
        }

        if ($value !== null && !is_scalar($value)) {
            throw new InvalidArgumentException(
                'JSON job data must contain only arrays, scalars, or null.'
            );
        }
    }
}
