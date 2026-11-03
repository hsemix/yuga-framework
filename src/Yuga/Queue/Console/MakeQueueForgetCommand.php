<?php

declare(strict_types=1);

namespace Yuga\Queue\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Yuga\Queue\Contracts\FailedJobTransportInterface;

class MakeQueueForgetCommand extends FailedJobCommand
{
	protected $name = 'queue:forget';

	protected $description = 'Delete one failed queue job.';

	protected function perform(
		FailedJobTransportInterface $transport
	): int {
		$deleted = $transport->forgetFailed(
			(string) $this->argument('failure-id')
		);

		$this->output->writeln(
			$deleted
				? 'Failed job deleted.'
				: 'Job not found or no longer failed.'
		);

		return $deleted ? self::SUCCESS : self::FAILURE;
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
		];
	}
}
