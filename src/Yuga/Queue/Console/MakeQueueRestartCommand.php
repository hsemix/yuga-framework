<?php

declare(strict_types=1);

namespace Yuga\Queue\Console;

use Throwable;
use Symfony\Component\Console\Output\OutputInterface;
use Yuga\Console\Command;

class MakeQueueRestartCommand extends Command
{
	protected $name = 'queue:restart';

	protected $description = 'Request graceful shutdown of running queue workers.';

	public function handle()
	{
		try {
			app('queue.restart')->request();

			$this->output->writeln(
				'Restart requested. Workers will exit after their current job.'
			);

			return self::SUCCESS;
		} catch (Throwable $exception) {
			$this->output->writeln(
				$exception->getMessage(),
				OutputInterface::OUTPUT_RAW
			);

			return self::FAILURE;
		}
	}
}
