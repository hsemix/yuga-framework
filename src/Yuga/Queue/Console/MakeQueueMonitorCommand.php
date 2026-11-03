<?php

namespace Yuga\Queue\Console;

use Throwable;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yuga\Console\Command;
use Yuga\Queue\Contracts\MonitorableTransportInterface;
use Yuga\Queue\Exceptions\UnsupportedCapabilityException;

/**
 * Queue Table creation.
 */
class MakeQueueMonitorCommand extends Command
{
	protected $name = 'queue:monitor';

	protected $description = 'Show a queue statistics snapshot.';

	public function handle()
	{
		try {
			$transport = app('queue.manager')->connection(
				$this->option('connection')
			);

			if (!$transport instanceof MonitorableTransportInterface) {
				throw new UnsupportedCapabilityException(
					'The selected transport does not support queue monitoring.'
				);
			}

			$queue = $this->option('queue');

			$this->output->writeln(
				json_encode([
					'queue' => $queue,
					'observed_at' => gmdate('Y-m-d\TH:i:s\Z'),
					'statistics' => $transport->statistics($queue),
				], JSON_THROW_ON_ERROR),
				OutputInterface::OUTPUT_RAW
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

	protected function getOptions()
	{
		return [
			['connection', null, InputOption::VALUE_REQUIRED, 'Connection name'],
			['queue', null, InputOption::VALUE_REQUIRED, 'Queue name; omit for all'],
		];
	}
}
