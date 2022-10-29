<?php

namespace Yuga\Providers\Console;

use FilesystemIterator;
use Tracy\FileSession;
use Yuga\Console\Command;
use Yuga\Support\FileSystem;
use Yuga\Providers\ServiceProvider;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class PackagePublishCommand extends Command
{
    protected $name = 'package:publish';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish all Publishable vendor Assets to their respective locations.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $group = $this->input->getArgument('group');

        if (is_null($group)) {
            return $this->publish();
        }

        $groups = explode(',', $group);

        foreach($groups as $group) {
            $this->publish($group);
        }
    }

    /**
     * Publish the assets for a given group name.
     *
     * @param  string|null  $group
     * @return void
     */
    protected function publish($group = null)
    {
        $paths = ServiceProvider::pathsToPublish($group);

        if (empty($paths)) {
            if (is_null($group)) {
                return $this->comment("Nothing to publish.");
            }

            return $this->comment("Nothing to publish for group [{$group}].");
        }

        foreach ($paths as $from => $to) {
            if (FileSystem::isFile($from)) {
                $this->publishFile($from, $to);
            } else if (is_dir($from)) {
                $this->publishDirectory($from, $to);
            } else {
                $this->error("Can't locate path: <{$from}>");
            }
        }

        if (is_null($group)) {
            return $this->info("Publishing complete!");
        }

        $this->info("Publishing complete for group [{$group}]!");
    }

    /**
     * Publish the file to the given path.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishFile($from, $to)
    {
        if (FileSystem::exists($to) && ! $this->option('force')) {
            return;
        }

        $directory = dirname($to);

        if (! is_dir($directory)) {
            FileSystem::createDir($directory, 0755);
        }

        FileSystem::copy($from, $to);

        $this->status($from, $to, 'File');
    }

    /**
     * Publish the directory to the given directory.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    protected function publishDirectory($from, $to)
    {
        $this->copyDirectory($from, $to);

        $this->status($from, $to, 'Directory');
    }

    /**
     * Copy a directory from one location to another.
     *
     * @param  string  $directory
     * @param  string  $destination
     * @param  bool  $force
     * @return bool
     */
    public function copyDirectory($directory, $destination)
    {
        if (!FileSystem::isDir($directory)) {
            return false;
        }

        if (!FileSystem::isDir($destination)) {
            FileSystem::createDir($destination, 0777);
        }

        $items = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);

        foreach ($items as $item) {
            $target = $destination .DIRECTORY_SEPARATOR .$item->getBasename();

            if ($item->isDir()) {
                if (! $this->copyDirectory($item->getPathname(), $target)) {
                    return false;
                }

                continue;
            }

            // The current item is a file.
            if (FileSystem::exists($target) && !$this->option('force')) {
                continue;
            } else if (!FileSystem::copy($item->getPathname(), $target)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Write a status message to the console.
     *
     * @param  string  $from
     * @param  string  $to
     * @param  string  $type
     * @return void
     */
    protected function status($from, $to, $type)
    {
        $from = str_replace(path(), '', realpath($from));

        $to = str_replace(path(), '', realpath($to));

        $this->output->writeln('<info>Copied '.$type.'</info> <comment>['.$from.']</comment> <info>To</info> <comment>['.$to.']</comment>');
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['group', InputArgument::OPTIONAL, 'The name of assets group being published.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
        ];
    }
}