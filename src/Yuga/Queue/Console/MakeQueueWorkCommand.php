<?php

declare(strict_types=1);

namespace Yuga\Queue\Console;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Yuga\Console\Command;
use Yuga\Queue\Worker;
use Yuga\Queue\WorkerResult;

class MakeQueueWorkCommand extends Command
{
	protected $name = 'queue:work';

	protected $description = 'Process jobs using a registered queue transport.';

	private bool $shouldQuit = false;

	public function handle()
	{
		$this->shouldQuit = false;

		$previousHandlers = [];
		$previousAsyncSignals = null;

		try {
			$queue = (string) $this->option('queue');

			if (trim($queue) === '') {
				throw new InvalidArgumentException(
					'Queue name must not be empty.'
				);
			}

			$sleep = $this->integerOption('sleep', 0);
			$maxJobs = $this->integerOption('max-jobs', 0);
			$maxTime = $this->integerOption('max-time', 0);
			$memory = $this->integerOption('memory', 0);

			$stopWhenEmpty = (bool) $this->option('stop');
			$once = (bool) $this->option('once');

			$settings = config('queue_transport')['worker'] ?? [];

			$transport = app('queue.manager')->transport(
				$this->argument('connection')
			);

			$worker = new Worker(
				transport: $transport,
				codecs: app('queue.codecs'),
				handlers: app('queue.handlers'),
				maxAttempts: (int) ($settings['max_attempts'] ?? 3),
				retryDelaySeconds: (int) ($settings['retry_delay'] ?? 5),
				reservationSeconds: (int) (
					$settings['reservation_seconds'] ?? 60
				),
				reporter: app('queue.reporter')
			);

			if (
				function_exists('pcntl_async_signals')
				&& function_exists('pcntl_signal')
				&& function_exists('pcntl_signal_get_handler')
			) {
				$previousAsyncSignals = pcntl_async_signals(true);

				foreach ([SIGTERM, SIGINT] as $signal) {
					$previousHandlers[$signal] =
						pcntl_signal_get_handler($signal);

					pcntl_signal($signal, function (int $signal): void {
						$this->shouldQuit = true;
					});
				}
			}

			$startedAt = hrtime(true);
			$processed = 0;

			$this->output->writeln(
				'Working queue: ' . $queue,
				OutputInterface::OUTPUT_RAW
			);

			$restartStore = app('queue.restart');
			$initialRestartToken = $restartStore->token();

			while (!$this->shouldQuit) {

				if ($restartStore->token() !== $initialRestartToken) {
					$this->output->writeln('Restart requested; exiting worker.');

					break;
				}

				if ($maxJobs > 0 && $processed >= $maxJobs) {
					break;
				}

				if (
					$maxTime > 0
					&& (hrtime(true) - $startedAt) / 1_000_000_000 >= $maxTime
				) {
					break;
				}

				if (
					$memory > 0
					&& memory_get_usage(true) >= $memory * 1024 * 1024
				) {
					break;
				}

				$result = $worker->runOnce($queue);

				if ($result === WorkerResult::Idle) {
					if ($stopWhenEmpty || $once) {
						break;
					}

					$this->sleepWhenIdle($sleep);

					continue;
				}

				$processed++;

				$this->output->writeln(
					'Job outcome: ' . $result->value,
					OutputInterface::OUTPUT_RAW
				);

				if ($once) {
					break;
				}
			}

			$this->output->writeln(
				"Worker stopped. Reservations processed: {$processed}"
			);

			return self::SUCCESS;
		} catch (Throwable $exception) {
			$this->output->writeln(
				$exception::class . ': ' . $exception->getMessage(),
				OutputInterface::OUTPUT_RAW
			);

			// A process supervisor can restart the worker.
			return self::FAILURE;
		} finally {
			foreach ($previousHandlers as $signal => $handler) {
				pcntl_signal($signal, $handler);
			}

			if ($previousAsyncSignals !== null) {
				pcntl_async_signals($previousAsyncSignals);
			}
		}
	}

	private function integerOption(string $name, int $minimum): int
	{
		$value = filter_var(
			$this->option($name),
			FILTER_VALIDATE_INT
		);

		if ($value === false || $value < $minimum) {
			throw new InvalidArgumentException(
				"Option --{$name} must be an integer >= {$minimum}."
			);
		}

		return $value;
	}

	private function sleepWhenIdle(int $seconds): void
	{
		// Short intervals allow shutdown signals to be handled promptly.
		$deadline = hrtime(true) + $seconds * 1_000_000_000;

		while (!$this->shouldQuit && hrtime(true) < $deadline) {
			$remaining = $deadline - hrtime(true);

			if ($remaining <= 0) {
				break;
			}

			usleep((int) min(100_000, max(1, $remaining / 1000)));
		}
	}

	protected function getArguments()
	{
		return [
			[
				'connection',
				InputArgument::OPTIONAL,
				'Configured queue connection name',
				null,
			],
		];
	}

	protected function getOptions()
	{
		return [
			['queue', null, InputOption::VALUE_REQUIRED, 'Queue name', 'default'],
			['stop', null, InputOption::VALUE_NONE, 'Stop when currently empty'],
			['once', null, InputOption::VALUE_NONE, 'Make one reservation attempt'],
			['sleep', null, InputOption::VALUE_REQUIRED, 'Idle sleep in seconds', 3],
			['max-jobs', null, InputOption::VALUE_REQUIRED, 'Reservation limit; 0 disables', 0],
			['max-time', null, InputOption::VALUE_REQUIRED, 'Runtime in seconds; 0 disables', 0],
			['memory', null, InputOption::VALUE_REQUIRED, 'Memory limit in MB; 0 disables', 128],
		];
	}
}
