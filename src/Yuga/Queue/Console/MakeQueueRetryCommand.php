<?php

declare(strict_types=1);

namespace Yuga\Queue\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Yuga\Queue\Contracts\FailedJobTransportInterface;

class MakeQueueRetryCommand extends FailedJobCommand
{
	protected $name = 'queue:retry';

	protected $description = 'Retry one failed queue job.';

	protected function perform(
		FailedJobTransportInterface $transport
	): int {
		$retried = $transport->retryFailed(
			(string) $this->argument('failure-id'),
			$this->integerOption('delay', 0)
		);

		$this->output->writeln(
			$retried
				? 'Failed job returned to the queue.'
				: 'Job not found or no longer failed.'
		);

		return $retried ? self::SUCCESS : self::FAILURE;
	}

	protected function getArguments()
	{
		return [
			['failure-id', InputArgument::REQUIRED, 'Failure ID from queue:failed'],
		];
	}

	protected function getOptions()
	{
		return [
			['connection', null, InputOption::VALUE_REQUIRED, 'Connection name'],
			['delay', null, InputOption::VALUE_REQUIRED, 'Retry delay in seconds', 0],
		];
	}
}
