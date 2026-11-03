<?php

declare(strict_types=1);

namespace Yuga\Queue\Console;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yuga\Queue\Contracts\FailedJobTransportInterface;

class MakeQueueFailedCommand extends FailedJobCommand
{
    protected $name = 'queue:failed';

    protected $description = 'List failed queue jobs.';

    protected function perform(
        FailedJobTransportInterface $transport
    ): int {
        $jobs = $transport->failedJobs(
            queue: $this->option('queue'),
            limit: $this->integerOption('limit', 1, 1000),
            offset: $this->integerOption('offset', 0)
        );

        if ($jobs === []) {
            $this->output->writeln('No failed jobs found.');

            return self::SUCCESS;
        }

        foreach ($jobs as $job) {
            $this->output->writeln(
                json_encode([
                    'failure_id' => $job->failureId,
                    'job_id' => $job->envelope->id,
                    'queue' => $job->queue,
                    'type' => $job->envelope->type,
                    'encoding' => $job->envelope->encoding,
                    'attempts' => $job->attempts,
                    'failed_at' => gmdate('Y-m-d\TH:i:s\Z', $job->failedAt),
                    'reason' => $job->reason,
                ], JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE),
                OutputInterface::OUTPUT_RAW
            );
        }

        return self::SUCCESS;
    }

    protected function getOptions()
    {
        return [
            ['connection', null, InputOption::VALUE_REQUIRED, 'Connection name'],
            ['queue', null, InputOption::VALUE_REQUIRED, 'Filter by queue'],
            ['limit', null, InputOption::VALUE_REQUIRED, 'Maximum results', 50],
            ['offset', null, InputOption::VALUE_REQUIRED, 'Results to skip', 0],
        ];
    }
}