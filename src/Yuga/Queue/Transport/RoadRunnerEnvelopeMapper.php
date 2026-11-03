<?php

declare(strict_types=1);

namespace Yuga\Queue\Transport;

use Spiral\RoadRunner\Jobs\Task\ReceivedTaskInterface;
use UnexpectedValueException;
use Yuga\Queue\Payload\JobEnvelope;

final class RoadRunnerEnvelopeMapper
{
    public function fromTask(ReceivedTaskInterface $task): JobEnvelope
    {
        $version = $this->header($task, 'yuga-version');

        if ($version !== (string) JobEnvelope::VERSION) {
            throw new UnexpectedValueException(
                "Unsupported Yuga envelope version: {$version}"
            );
        }

        $type = $this->header($task, 'yuga-type');

        if ($type !== $task->getName()) {
            throw new UnexpectedValueException(
                'Task name does not match the Yuga job type.'
            );
        }

        return new JobEnvelope(
            id: $this->header($task, 'yuga-id'),
            type: $type,
            encoding: $this->header($task, 'yuga-encoding'),
            body: $task->getPayload(),
            version: (int) $version
        );
    }

    private function header(
        ReceivedTaskInterface $task,
        string $name
    ): string {
        $values = array_values($task->getHeader($name));

        if (
            count($values) !== 1
            || !is_string($values[0])
            || trim($values[0]) === ''
        ) {
            throw new UnexpectedValueException(
                "Expected one nonempty [{$name}] header."
            );
        }

        return $values[0];
    }
}
