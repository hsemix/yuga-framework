<?php

declare(strict_types=1);

namespace Yuga\Queue\Contracts;

use Yuga\Queue\Failure\StoredFailure;

interface InspectableFailureStoreInterface extends FailureStoreInterface
{
    public function find(string $failureId): ?StoredFailure;

    /** @return list<StoredFailure> */
    public function listing(
        ?string $source = null,
        int $limit = 50,
        int $offset = 0
    ): array;
}
