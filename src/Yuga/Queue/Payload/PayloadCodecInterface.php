<?php

declare(strict_types=1);

namespace Yuga\Queue\Payload;

interface PayloadCodecInterface
{
    public function name(): string;

    public function encode(mixed $value): string;

    public function decode(string $payload): mixed;
}
