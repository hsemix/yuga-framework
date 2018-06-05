<?php
namespace Yuga\Console\Commands;

use Yuga\Console\Command;
use Symfony\Component\Console\Input\InputOption;

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
    protected $description = "Start the Yuga Application on the PHP development server";

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->checkPhpVersion();

        chdir(path());

        $host = $this->input->getOption('host');

        $port = $this->input->getOption('port');

        $public = path('public');

        $this->info("Yuga Framework development Server started on http://{$host}:{$port}");

        passthru('"'.PHP_BINARY.'"'." -S {$host}:{$port} -t \"{$public}\" server.php");
    }

    /**
     * Check the current PHP version is >= 5.4.
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function checkPhpVersion()
    {
        if (version_compare(PHP_VERSION, '5.5.0', '<')) {
            throw new \Exception('This PHP binary is not version 5.5 or greater.');
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['host', null, InputOption::VALUE_OPTIONAL, 'The host address to serve the application on.', 'localhost'],
            ['port', null, InputOption::VALUE_OPTIONAL, 'The port to serve the application on.', 8000],
        ];
    }

}