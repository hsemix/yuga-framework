<?php

declare(strict_types=1);

namespace Yuga\Queue\Failure;

final class ReplayClaim
{
    public function __construct(
        public readonly StoredFailure $failure,
        public readonly string $token
    ) {}
}
