<?php

namespace Yuga\Database\Console;


use Yuga\Console\Command;

use Nova\Support\Facades\Config;
use Yuga\Database\Console\Backup\Console;
use Yuga\Database\Console\Backup\DatabaseBuilder;


class BaseCommand extends Command
{
    protected $databaseBuilder;

    protected $console;
    protected $config;


    public function __construct(DatabaseBuilder $databaseBuilder, Console $console)
    {
        parent::__construct();

        $this->databaseBuilder = $databaseBuilder;
        $this->console = $console;
        $this->config   = app()->config->load('config.Config');
    }

    public function getDatabase($database = null)
    {
        $database = $database ?: $this->config->get('db.defaultDriver');

        $realConfig = $this->config->get('db.' . $database);

        // print_r($realConfig);
        // die();
        return $this->databaseBuilder->getDatabase($realConfig);
    }

    protected function getDumpsPath()
    {
        $path = $this->config->get('db.backup.path');

        return rtrim($path, '\\/') . DIRECTORY_SEPARATOR;
    }

    public function enableCompression()
    {
        return $this->config->set('db.backup.compress', true);
    }

    public function disableCompression()
    {
        return $this->config->set('db.backup.compress', false);
    }

    public function isCompressionEnabled()
    {
        return $this->config->get('db.backup.compress');
    }

    public function isCompressed($fileName)
    {
        return (pathinfo($fileName, PATHINFO_EXTENSION) === "gz");
    }
}
