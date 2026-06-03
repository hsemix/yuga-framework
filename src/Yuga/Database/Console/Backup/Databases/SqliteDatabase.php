<?php

namespace Yuga\Database\Console\Backup\Databases;

use Yuga\Database\Console\Backup\Console;

class SqliteDatabase implements DatabaseInterface
{
    protected $config;


    public function __construct(protected \Yuga\Database\Console\Backup\Console $console, protected $databaseFile)
    {
        $this->config   = app()->config->load('config.Config');
    }

    public function dump($destinationFile)
    {
        $command = sprintf('cp %s %s',
            escapeshellarg((string) $this->databaseFile),
            escapeshellarg((string) $destinationFile)
        );

        return $this->console->run($command);
    }

    public function restore($sourceFile)
    {
        $command = sprintf('cp -f %s %s',
            escapeshellarg((string) $sourceFile),
            escapeshellarg((string) $this->databaseFile)
        );

        return $this->console->run($command);
    }

    public function getDatabase()
    {
        $databaseFile = basename((string) $this->databaseFile);

        return preg_replace('/\.sqlite$/s', '', $databaseFile);
    }

    public function getFileExtension()
    {
        return 'sqlite';
    }
}
