<?php

declare(strict_types=1);

namespace Yuga\Queue\Console;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Throwable;
use Yuga\Console\Command;
use Yuga\Runtime\Support\RoadRunnerServer;

class MakeQueueServeCommand extends Command
{
    protected $name = 'queue:serve';

    protected $description = 'Start a queue server for a configured connection.';

    public function handle()
    {
        try {
            if (
                isset($_SERVER['RR_MODE'])
                || getenv('RR_MODE') !== false
            ) {
                throw new InvalidArgumentException(
                    'Run queue:serve outside a RoadRunner worker.'
                );
            }

            $name = (string) $this->argument('connection');
            $queues = config('queue_transport');
            $connection = $queues['connections'][$name] ?? null;

            if (
                !is_array($connection)
                || ($connection['driver'] ?? null) !== 'roadrunner'
            ) {
                throw new InvalidArgumentException(
                    'This launcher currently supports RoadRunner connections.'
                );
            }

            $settings = config('runtime_server')['roadrunner'] ?? [];
            $configuration = $settings['queue_configs'][$name] ?? null;
            $binary = $settings['binary'] ?? null;

            if (!is_string($configuration) || !is_string($binary)) {
                throw new InvalidArgumentException(
                    'Configure a RoadRunner binary and queue-only profile '
                        . 'for this connection.'
                );
            }

            return (new RoadRunnerServer())->run(
                $binary,
                $configuration,
                path()
            );
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception->getMessage() . PHP_EOL);

            return self::FAILURE;
        }
    }

    protected function getArguments()
    {
        return [
            [
                'connection',
                InputArgument::REQUIRED,
                'Queue connection to serve',
            ],
        ];
    }
}
