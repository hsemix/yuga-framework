<?php

declare(strict_types=1);

namespace Yuga\Queue\Console;

use InvalidArgumentException;
use Spiral\RoadRunner\Jobs\Consumer;
use Symfony\Component\Console\Input\InputArgument;
use Throwable;
use Yuga\Console\Command;
use Yuga\Queue\Consumers\RoadRunnerConsumer;
use Yuga\Queue\Transport\RoadRunnerEnvelopeMapper;

class MakeRoadRunnerConsumeCommand extends Command
{
    protected $name = 'queue:consume-roadrunner';

    protected $description = 'Consume Yuga jobs inside a RoadRunner Jobs worker.';

    public function handle()
    {
        try {
            $mode = $_SERVER['RR_MODE']
                ?? getenv('RR_MODE')
                ?: null;

            if ($mode !== 'jobs') {
                throw new InvalidArgumentException(
                    'This command must be launched by RoadRunner in Jobs mode.'
                );
            }

            $config = config('queue_transport');
            $name = (string) $this->argument('connection');
            $connection = $config['connections'][$name] ?? null;

            if (
                !is_array($connection)
                || ($connection['driver'] ?? null) !== 'roadrunner'
            ) {
                throw new InvalidArgumentException(
                    'Select a configured RoadRunner connection.'
                );
            }

            $source = $connection['failure_source'] ?? null;

            if (!is_string($source) || trim($source) === '') {
                throw new InvalidArgumentException(
                    'The RoadRunner connection requires failure_source.'
                );
            }

            $settings = $connection['worker']
                ?? $config['worker']
                ?? [];

            $maxAttempts = $settings['max_attempts'] ?? 3;
            $retryDelay = $settings['retry_delay'] ?? 5;

            if (
                !is_int($maxAttempts) || $maxAttempts < 1
                || !is_int($retryDelay) || $retryDelay < 0
            ) {
                throw new InvalidArgumentException(
                    'Invalid RoadRunner worker settings.'
                );
            }

            $consumer = new RoadRunnerConsumer(
                consumer: new Consumer(),
                mapper: new RoadRunnerEnvelopeMapper(),
                executor: app('queue.executor'),
                reporter: app('queue.reporter'),
                failureStore: app('queue.failures'),
                failureNotifier: app('queue.failure-notifier'),
                failureSource: $source,
                maxAttempts: $maxAttempts,
                retryDelaySeconds: $retryDelay
            );

            $consumer->run();

            return self::SUCCESS;
        } catch (Throwable $exception) {
            // Keep errors away from the STDOUT relay.
            do {
                fwrite(
                    STDERR,
                    $exception::class . ': '
                        . $exception->getMessage() . PHP_EOL
                );
            } while ($exception = $exception->getPrevious());

            return self::FAILURE;
        }
    }

    protected function getArguments()
    {
        return [
            [
                'connection',
                InputArgument::OPTIONAL,
                'RoadRunner connection name',
                'roadrunner',
            ],
        ];
    }
}
