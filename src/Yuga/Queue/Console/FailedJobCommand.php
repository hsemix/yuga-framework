<?php

declare(strict_types=1);

namespace Yuga\Queue\Console;

use InvalidArgumentException;
use Throwable;
use Symfony\Component\Console\Output\OutputInterface;
use Yuga\Console\Command;
use Yuga\Queue\Contracts\FailedJobTransportInterface;
use Yuga\Queue\Exceptions\UnsupportedCapabilityException;

abstract class FailedJobCommand extends Command
{
    public function handle()
    {
        try {
            $transport = app('queue.manager')->connection(
                $this->option('connection')
            );

            if (!$transport instanceof FailedJobTransportInterface) {
                throw new UnsupportedCapabilityException(
                    'The selected transport does not support failed-job management.'
                );
            }

            return $this->perform($transport);
        } catch (Throwable $exception) {
            $this->output->writeln(
                $exception::class . ': ' . $exception->getMessage(),
                OutputInterface::OUTPUT_RAW
            );

            return self::FAILURE;
        }
    }

    abstract protected function perform(
        FailedJobTransportInterface $transport
    ): int;

    protected function integerOption(
        string $name,
        int $minimum,
        ?int $maximum = null
    ): int {
        $value = filter_var(
            $this->option($name),
            FILTER_VALIDATE_INT
        );

        if (
            $value === false
            || $value < $minimum
            || ($maximum !== null && $value > $maximum)
        ) {
            throw new InvalidArgumentException(
                "Invalid integer value for --{$name}."
            );
        }

        return $value;
    }
}
