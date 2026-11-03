<?php

declare(strict_types=1);

namespace Yuga\Queue\Payload;

use InvalidArgumentException;
use UnexpectedValueException;
use Yuga\Queue\JobRegistry;

final class PhpSerializeCodec implements PayloadCodecInterface
{
    /**
     * @param list<class-string> $allowedClasses
     */
    public function __construct(
        private readonly array|JobRegistry $allowedClasses = [],
        private readonly array $additionalClasses = []
    ) {}

    public function name(): string
    {
        return 'php';
    }

    public function encode(mixed $value): string
    {
        if (!is_object($value)) {
            throw new InvalidArgumentException(
                'PHP job payloads must be objects.'
            );
        }

        return serialize($value);
    }

    public function decode(string $payload): object
    {

        $allowedClasses = $this->allowedClasses instanceof JobRegistry
            ? $this->allowedClasses->classes()
            : $this->allowedClasses;

        $allowedClasses = array_values(array_unique([
            ...$allowedClasses,
            ...$this->additionalClasses,
        ]));

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

            $job = unserialize($payload, [
                'allowed_classes' => $allowedClasses,
                'max_depth' => 128,
            ]);
        } finally {
            restore_error_handler();
        }

        if (!is_object($job) || $job instanceof \__PHP_Incomplete_Class) {
            throw new UnexpectedValueException(
                'The payload does not contain an allowed PHP job object.'
            );
        }

        return $job;
    }
}
