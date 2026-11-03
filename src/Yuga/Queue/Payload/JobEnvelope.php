<?php

declare(strict_types=1);

namespace Yuga\Queue\Payload;

use InvalidArgumentException;
use UnexpectedValueException;

final class JobEnvelope
{
    public const VERSION = 1;

    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly string $encoding,
        public readonly string $body,
        public readonly int $version = self::VERSION
    ) {
        if (trim($id) === '' || trim($type) === '') {
            throw new InvalidArgumentException(
                'Job ID and type must not be empty.'
            );
        }

        if (trim($encoding) === '') {
            throw new InvalidArgumentException(
                'Job encoding must not be empty.'
            );
        }

        if ($version !== self::VERSION) {
            throw new InvalidArgumentException(
                "Unsupported job envelope version: {$version}"
            );
        }
    }

    public static function create(
        string $type,
        mixed $value,
        PayloadCodecInterface $codec
    ): self {
        return new self(
            id: bin2hex(random_bytes(16)),
            type: $type,
            encoding: $codec->name(),
            body: $codec->encode($value)
        );
    }

    public function decode(PayloadCodecInterface $codec): mixed
    {
        if ($codec->name() !== $this->encoding) {
            throw new UnexpectedValueException(
                "Expected {$this->encoding} codec; received {$codec->name()}."
            );
        }

        return $codec->decode($this->body);
    }

    /**
     * The raw body is intentionally excluded.
     */
    public function headers(): array
    {
        return [
            'version' => $this->version,
            'id' => $this->id,
            'type' => $this->type,
            'encoding' => $this->encoding,
        ];
    }
}
