<?php

namespace Yuga\Database\Console\Backup\Databases;

use Yuga\Database\Console\Backup\Console;


class PostgresDatabase implements DatabaseInterface
{
    protected $config;


    public function __construct(protected \Yuga\Database\Console\Backup\Console $console, protected $database, protected $user, protected $password, protected $host)
    {
        $this->config   = app()->config->load('config.Config');
    }

    public function dump($destinationFile)
    {
        $command = sprintf('PGPASSWORD=%s pg_dump -Fc --no-acl --no-owner -h %s -U %s %s > %s',
            escapeshellarg((string) $this->password),
            escapeshellarg((string) $this->host),
            escapeshellarg((string) $this->user),
            escapeshellarg((string) $this->database),
            escapeshellarg((string) $destinationFile)
        );

        return $this->console->run($command);
    }

    public function restore($sourceFile)
    {
        $command = sprintf('PGPASSWORD=%s pg_restore --verbose --clean --no-acl --no-owner -h %s -U %s -d %s %s',
            escapeshellarg((string) $this->password),
            escapeshellarg((string) $this->host),
            escapeshellarg((string) $this->user),
            escapeshellarg((string) $this->database),
            escapeshellarg((string) $sourceFile)
        );

        return $this->console->run($command);
    }

    public function getDatabase()
    {
        return $this->database;
    }

    public function getFileExtension()
    {
        return 'dump';
    }
}
