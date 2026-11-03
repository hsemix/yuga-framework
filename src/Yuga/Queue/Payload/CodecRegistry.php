<?php

declare(strict_types=1);

namespace Yuga\Queue\Payload;

use InvalidArgumentException;

final class CodecRegistry
{
    /** @var array<string, PayloadCodecInterface> */
    private array $codecs = [];

    public function register(PayloadCodecInterface $codec): self
    {
        $name = $codec->name();

        if (trim($name) === '') {
            throw new InvalidArgumentException(
                'Payload codec name must not be empty.'
            );
        }

        if (isset($this->codecs[$name])) {
            throw new InvalidArgumentException(
                "Payload codec [{$name}] is already registered."
            );
        }

        $this->codecs[$name] = $codec;

        return $this;
    }

    public function get(string $name): PayloadCodecInterface
    {
        return $this->codecs[$name]
            ?? throw new InvalidArgumentException(
                "Payload codec [{$name}] is not registered."
            );
    }
}
