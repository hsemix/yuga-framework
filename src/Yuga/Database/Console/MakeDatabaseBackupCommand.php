<?php

namespace Yuga\Database\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Yuga\Console\Command;

class MakeDatabaseBackupCommand extends BaseCommand
{
    protected $name = 'db:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup the default database to `app/Database/Backup`';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $config = $this->input->getOption('database');

        $database = $this->getDatabase($config);

        $this->checkDumpFolder();

        //
        $fileName = $this->argument('filename');

        if (!empty($fileName)) {
            $this->filePath = realpath($fileName);

            $this->fileName = basename($this->filePath);
        } else {
            $this->fileName = str_replace('_', '-', $database->getDatabase()).'_'.date('Y-m-d_H-i-s').'.'.$database->getFileExtension();

            $this->filePath = $this->getDumpsPath().$this->fileName;
        }

        $status = $database->dump($this->filePath);

        if ($status === true) {
            if ($this->isCompressionEnabled()) {
                $this->compress();

                $this->fileName .= '.gz';
                $this->filePath .= '.gz';
            }

            if (!empty($fileName)) {
                $this->info('Database backup was successful. Saved to '.$this->filePath);
            } else {
                $this->info('Database backup was successful. '.$this->fileName.' was saved in the dumps folder.');
            }
        } else {
            $this->error('Database backup failed. '.$status);
        }
    }

    /**
     * Perform Gzip compression on file.
     *
     * @return bool Status of command
     */
    protected function compress()
    {
        $command = sprintf('gzip -9 %s', $this->filePath);

        return $this->console->run($command);
    }

    protected function checkDumpFolder()
    {
        $dumpsPath = $this->getDumpsPath();

        if (!is_dir($dumpsPath)) {
            mkdir($dumpsPath, 0777, true);
        }
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['filename', InputArgument::OPTIONAL, 'Filename or -path for the dump.'],
        ];
    }

    protected function getOptions()
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to backup'],
        ];
    }
}
