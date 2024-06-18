<?php

namespace Yuga\Database\Console;

use Yuga\Support\FileSystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class MakeDatabaseRestoreCommand extends BaseCommand
{
    protected $name = 'db:restore';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore a database dump from `app/Database/Backup`';

    protected $filePath;
    protected $fileName;
    protected $database;

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->database = $this->getDatabase($this->input->getOption('database'));

        $fileName = $this->argument('dump');

        if ($fileName) {
            $this->restoreDump($fileName);
        } else {
            $this->listAllDumps();
        }
    }

    protected function restoreDump($fileName)
    {
        $sourceFile = $this->getDumpsPath() . $fileName;

        $compressed = false;

        if ($this->isCompressed($sourceFile)) {
            $sourceFile = $this->uncompress($sourceFile);

            $compressed = true;
        }

        $status = $this->database->restore($this->getUncompressedFileName($sourceFile));

        if ($compressed) {
            FileSystem::delete($sourceFile);
        }

        if ($status === true) {
            $this->info($fileName . ' was successfully restored.');
        } else {
            $this->error('Database restore failed.');
        }
    }

    protected function listAllDumps()
    {
        $finder = new Finder();

        $finder->files()->in($this->getDumpsPath());

        if ($finder->count() > 0) {
            $this->info("Please select one of the following dumps: \n");

            $finder->sortByName();

            $count = count($finder);

            $i=0;

            foreach ($finder as $dump) {
                $i++;

                if($i != $count) {
                    $this->line($dump->getFilename());
                } else {
                    $this->line($dump->getFilename() ."\n");
                }
            }
        } else {
            $this->info('You haven\'t saved any dumps. Please provide at lease one as an argument');
        }
    }

    /**
     * Uncompress a GZip compressed file
     *
     * @param string $fileName      Relative or absolute path to file
     * @return string               Name of uncompressed file (without .gz extension)
     */
    protected function uncompress($fileName)
    {
        $fileNameUncompressed = $this->getUncompressedFileName($fileName);

        $command = sprintf('gzip -dc %s > %s', $fileName, $fileNameUncompressed);

        if ($this->console->run($command) !== true) {
            $this->error( 'Uncompress of gzipped file failed.');
        }

        return $fileNameUncompressed;
    }

    /**
     * Remove uncompressed files
     *
     * Files are temporarily uncompressed for usage in restore. We do not need these copies
     * permanently.
     *
     * @param string $fileName      Relative or absolute path to file
     * @return boolean              Success or failure of cleanup
     */
    protected function cleanup($fileName)
    {
        $status = true;

        $fileNameUncompressed = $this->getUncompressedFileName($fileName);

        if ($fileName !== $fileNameUncompressed) {
            $status = FileSystem::delete($fileName);
        }

        return $status;
    }

    /**
     * Retrieve filename without Gzip extension
     *
     * @param string $fileName      Relative or absolute path to file
     * @return string               Filename without .gz extension
     */
    protected function getUncompressedFileName($fileName)
    {
        return preg_replace('"\.gz$"', '', $fileName);
    }

    protected function getArguments()
    {
        return [
            ['dump', InputArgument::OPTIONAL, 'Filename of the dump']
        ];
    }

    protected function getOptions()
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to restore to'],
        ];
    }
}