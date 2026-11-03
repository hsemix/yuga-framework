<?php

namespace Yuga\Console\Commands;

use Yuga\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Yuga\Runtime\Support\RoadRunnerServer;

class ServeCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the configured application HTTP server.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->checkPhpVersion();

        $runtimeSettings = config('runtime_server');
        $runtime = $runtimeSettings['http_runtime'] ?? 'php';

        if ($runtime === 'roadrunner') {
            return $this->serveRoadRunner(
                $runtimeSettings['roadrunner'] ?? []
            );
        }

        if ($runtime !== 'php') {
            throw new \InvalidArgumentException(
                "The start command does not yet support runtime [{$runtime}]."
            );
        }

        chdir(path());

        $host = $this->input->getOption('host');

        $port = $this->input->getOption('port');

        $public = path('public');

        if ($port == 8000) {
            for ($i = $port; $i < 9001; $i++) {
                $port = $i;
                if (!@fsockopen($host, $i, $errono, $message)) {
                    goto server;
                }
                $this->error("Yuga Framework development Server couldn't start on http://{$host}:{$port}");
                $this->info("Retrying...");
            }
        }

        server:
        $this->info("Yuga Framework development Server started on http://{$host}:{$port}");
        passthru('"' . PHP_BINARY . '"' . " -S {$host}:{$port} -t \"{$public}\" server.php");
    }

    private function serveRoadRunner(array $settings): int
    {
        if (
            isset($_SERVER['RR_MODE'])
            || getenv('RR_MODE') !== false
        ) {
            throw new \RuntimeException(
                'Run start outside a RoadRunner worker.'
            );
        }

        $queues = config('queue_transport');

        $connectionName = array_key_exists('queue_connection', $settings)
            ? $settings['queue_connection']
            : ($queues['default'] ?? null);

        if (
            $connectionName !== null
            && (!is_string($connectionName) || trim($connectionName) === '')
        ) {
            throw new \InvalidArgumentException(
                'The runtime queue connection must be a name or null.'
            );
        }

        $connection = $connectionName === null
            ? null
            : ($queues['connections'][$connectionName] ?? null);

        if ($connectionName !== null && !is_array($connection)) {
            throw new \InvalidArgumentException(
                "Queue connection [{$connectionName}] is not configured."
            );
        }

        if (($connection['driver'] ?? null) === 'roadrunner') {
            $configuration =
                $settings['combined_configs'][$connectionName] ?? null;
        } else {
            $configuration = $settings['http_config'] ?? null;
        }

        $binary = $settings['binary'] ?? null;

        if (!is_string($configuration) || !is_string($binary)) {
            throw new \InvalidArgumentException(
                'Configure the RoadRunner binary and selected server profile.'
            );
        }

        return (new RoadRunnerServer())->run(
            $binary,
            $configuration,
            path()
        );
    }

    /**
     * Check the current PHP version is >= 5.4.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function checkPhpVersion() {}

    /**
     * Get the console command options.
     *
     * @return array
     */
    #[\Override]
    protected function getOptions()
    {
        return [
            ['host', null, InputOption::VALUE_OPTIONAL, 'The host address to serve the application on.', 'localhost'],
            ['port', null, InputOption::VALUE_OPTIONAL, 'The port to serve the application on.', 8000],
        ];
    }
}
