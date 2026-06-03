<?php

namespace Yuga\Database\Console\Backup\Databases;

use Yuga\Database\Console\Backup\Console;

class MySQLDatabase implements DatabaseInterface
{
    protected $config;


    public function __construct(protected \Yuga\Database\Console\Backup\Console $console, protected $database, protected $user, protected $password, protected $host, protected $port)
    {
        $this->config   = app()->config->load('config.Config');
    }

    public function dump($destinationFile)
    {
        $command = sprintf('%s --user=%s --password=%s --host=%s --port=%s %s > %s',
            $this->getDumpCommandPath(),
            escapeshellarg((string) $this->user),
            escapeshellarg((string) $this->password),
            escapeshellarg((string) $this->host),
            escapeshellarg((string) $this->port),
            escapeshellarg((string) $this->database),
            escapeshellarg((string) $destinationFile)
        );

        return $this->console->run($command);
    }

    public function restore($sourceFile)
    {
        $command = sprintf('%s --user=%s --password=%s --host=%s --port=%s %s < %s',
            $this->getRestoreCommandPath(),
            escapeshellarg((string) $this->user),
            escapeshellarg((string) $this->password),
            escapeshellarg((string) $this->host),
            escapeshellarg((string) $this->port),
            escapeshellarg((string) $this->database),
            escapeshellarg((string) $sourceFile)
        );

        return $this->console->run($command);
    }

    protected function getDumpCommandPath()
    {
        return $this->config->get('db.backup.mysql.dumpCommandPath');
    }

    protected function getRestoreCommandPath()
    {
        return $this->config->get('db.backup.mysql.restoreCommandPath');
    }

    public function getDatabase()
    {
        return $this->database;
    }

    public function getFileExtension()
    {
        return 'sql';
    }
}
