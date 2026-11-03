<?php

declare(strict_types=1);

namespace Yuga\Queue;

enum WorkerResult: string
{
    case Idle = 'idle';
    case Completed = 'completed';
    case Released = 'released';
    case Failed = 'failed';
}
