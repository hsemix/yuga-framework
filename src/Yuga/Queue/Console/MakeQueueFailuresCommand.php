<?php

declare(strict_types=1);

namespace Yuga\Queue\Console;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Yuga\Console\Command;
use Yuga\Queue\Contracts\InspectableFailureStoreInterface;

class MakeQueueFailuresCommand extends Command
{
    protected $name = 'queue:failures';

    protected $description = 'Inspect failures retained in the failure store.';

    public function handle()
    {
        try {
            $store = app('queue.failures');

            if (!$store instanceof InspectableFailureStoreInterface) {
                throw new \RuntimeException(
                    'The configured failure store does not support inspection.'
                );
            }

            $id = $this->argument('id');

            if ($id !== null) {
                $failure = $store->find($id);

                if ($failure === null) {
                    $this->output->writeln('Failure not found.');

                    return self::FAILURE;
                }

                $failures = [$failure];
            } else {
                $limit = filter_var(
                    $this->option('limit'),
                    FILTER_VALIDATE_INT
                );

                $offset = filter_var(
                    $this->option('offset'),
                    FILTER_VALIDATE_INT
                );

                if (
                    $limit === false || $limit < 1 || $limit > 1000
                    || $offset === false || $offset < 0
                ) {
                    throw new InvalidArgumentException(
                        'Limit must be 1–1000 and offset must be nonnegative.'
                    );
                }

                $failures = $store->listing(
                    source: $this->option('source'),
                    limit: $limit,
                    offset: $offset
                );
            }

            foreach ($failures as $failure) {
                $this->output->writeln(
                    json_encode([
                        'failure_id' => $failure->id,
                        'source' => $failure->source,
                        'delivery_id' => $failure->deliveryId,
                        'destination' => $failure->destination,
                        'job_id' => $failure->envelope->id,
                        'type' => $failure->envelope->type,
                        'encoding' => $failure->envelope->encoding,
                        'attempts' => $failure->attempts,
                        'reason' => $failure->reason,
                        'failed_at' => gmdate(
                            'Y-m-d\TH:i:s\Z',
                            $failure->failedAt
                        ),
                    ], JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE),
                    OutputInterface::OUTPUT_RAW
                );
            }

            if ($failures === []) {
                $this->output->writeln('No stored failures found.');
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->output->writeln(
                $exception->getMessage(),
                OutputInterface::OUTPUT_RAW
            );

            return self::FAILURE;
        }
    }

    protected function getArguments()
    {
        return [
            ['id', InputArgument::OPTIONAL, 'Inspect one failure ID'],
        ];
    }

    protected function getOptions()
    {
        return [
            ['source', null, InputOption::VALUE_REQUIRED, 'Filter the listing by source'],
            ['limit', null, InputOption::VALUE_REQUIRED, 'Maximum results', 50],
            ['offset', null, InputOption::VALUE_REQUIRED, 'Results to skip', 0],
        ];
    }
}
