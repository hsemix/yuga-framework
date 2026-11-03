<?php

declare(strict_types=1);

namespace Yuga\Queue\Payload;

use InvalidArgumentException;
use UnexpectedValueException;
use Yuga\Queue\Jobs\ClosureJob;

use function Opis\Closure\serialize as opisSerialize;
use function Opis\Closure\unserialize as opisUnserialize;

final class ClosureCodec implements PayloadCodecInterface
{
    public function name(): string
    {
        return 'opis-closure-v4';
    }

    public function encode(mixed $value): string
    {
        if (!$value instanceof ClosureJob) {
            throw new InvalidArgumentException(
                'The closure codec requires a ClosureJob.'
            );
        }

        return opisSerialize($value);
    }

    public function decode(string $payload): ClosureJob
    {
        set_error_handler(
            static function (
                int $severity,
                string $message,
                string $file,
                int $line
            ): never {
                throw new UnexpectedValueException($message);
            },
            E_WARNING | E_NOTICE
        );

        try {
            $job = opisUnserialize($payload);
        } finally {
            restore_error_handler();
        }

        if (!$job instanceof ClosureJob) {
            throw new UnexpectedValueException(
                'The payload does not contain a ClosureJob.'
            );
        }

        return $job;
    }
}
