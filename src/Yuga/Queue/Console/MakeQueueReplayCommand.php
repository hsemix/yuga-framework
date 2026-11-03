<?php

declare(strict_types=1);

namespace Yuga\Queue\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Yuga\Console\Command;
use Yuga\Queue\Failure\FailureReplayer;

class MakeQueueReplayCommand extends Command
{
    protected $name = 'queue:replay';

    protected $description = 'Replay one retained failure.';

    public function handle()
    {
        try {
            $source = $this->option('source');
            $delay = filter_var(
                $this->option('delay'),
                FILTER_VALIDATE_INT
            );

            if (!is_string($source) || trim($source) === '') {
                throw new \InvalidArgumentException(
                    'Provide --source for the stored failure.'
                );
            }

            if ($delay === false || $delay < 0) {
                throw new \InvalidArgumentException(
                    'Delay must be a nonnegative integer.'
                );
            }

            $replayers = app('queue.replayers');
            $replayer = $replayers[$source] ?? null;

            if (!$replayer instanceof FailureReplayer) {
                throw new \InvalidArgumentException(
                    'No replayer is registered for this source.'
                );
            }

            $jobId = $replayer->replay(
                (string) $this->argument('failure-id'),
                $delay
            );

            $this->output->writeln(
                "Replay published for Yuga job {$jobId}.",
                OutputInterface::OUTPUT_RAW
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            // Include the underlying publication/storage error.
            do {
                $this->output->writeln(
                    $exception->getMessage(),
                    OutputInterface::OUTPUT_RAW
                );
            } while ($exception = $exception->getPrevious());

            return self::FAILURE;
        }
    }

    protected function getArguments()
    {
        return [
            ['failure-id', InputArgument::REQUIRED, 'Stored failure ID'],
        ];
    }

    protected function getOptions()
    {
        return [
            ['source', null, InputOption::VALUE_REQUIRED, 'Failure source'],
            ['delay', null, InputOption::VALUE_REQUIRED, 'Delay in seconds', 0],
        ];
    }
}
