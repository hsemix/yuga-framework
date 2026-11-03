<?php

declare(strict_types=1);

namespace Yuga\Console\Commands;

use RuntimeException;
use Symfony\Component\Console\Input\InputOption;
use Throwable;
use Yuga\Console\Command;
use Yuga\Runtime\Adapters\RoadRunnerRuntime;
use Yuga\Runtime\Kernel;

class MakeRuntimeWorkerCommand extends Command
{
    protected $name = 'runtime:worker';

    protected $description = 'Run a worker managed by RoadRunner.';

    public function handle()
    {
        try {
            $mode = $_SERVER['RR_MODE'] ?? getenv('RR_MODE');

            return match ($mode) {
                'http' => $this->serveHttp(),
                'jobs' => $this->serveJobs(),
                default => throw new RuntimeException(
                    'This command requires RoadRunner HTTP or Jobs mode.'
                ),
            };
        } catch (Throwable $exception) {
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

    private function serveHttp(): int
    {
        if (!$this->yuga instanceof Kernel) {
            throw new RuntimeException(
                'The application must implement Yuga\Runtime\Kernel.'
            );
        }

        (new RoadRunnerRuntime())->run($this->yuga);

        return self::SUCCESS;
    }

    private function serveJobs(): int
    {
        return $this->call('queue:consume-roadrunner', [
            'connection' => $this->option('queue-connection'),
        ]);
    }

    protected function getOptions()
    {
        return [
            [
                'queue-connection',
                null,
                InputOption::VALUE_REQUIRED,
                'Queue connection used by Jobs workers',
                'roadrunner',
            ],
        ];
    }
}
