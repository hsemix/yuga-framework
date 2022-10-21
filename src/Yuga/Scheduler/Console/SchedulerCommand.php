<?php

namespace Yuga\Scheduler\Console;

use DateTime;
use Yuga\Console\Command;

/**
 * Base functionality for enable/disable.
 */
abstract class SchedulerCommand extends Command
{
    /**
     * Config File.
     */
    protected $config = null;

    /**
     * Get Config File.
     */
    protected function getConfig()
    {
        $this->config = config('scheduler');
    }

    /**
     * Saves the settings.
     */
    protected function saveSettings($status)
    {
        $this->getConfig();

        if (!file_exists($this->config['FilePath'].'/'.$this->config['FileName'])) {
            // dir doesn't exist, make it
            if (!is_dir($this->config['FilePath'])) {
                mkdir($this->config['FilePath']);
            }

            $settings = [
                'status' => $status,
                'time'   => (new DateTime('now'))->format('Y-m-d H:i:s'),
            ];

            // write the file with json content
            file_put_contents(
                $this->config['FilePath'].'/'.$this->config['FileName'],
                json_encode(
                    $settings,
                    JSON_PRETTY_PRINT
                )
            );

            return $settings;
        }

        return false;
    }

    /**
     * Gets the settings, if they have never been
     * saved, save them.
     */
    protected function getSettings()
    {
        $this->getConfig();

        if (file_exists($this->config['FilePath'].'/'.$this->config['FileName'])) {
            $data = json_decode(file_get_contents($this->config['FilePath'].'/'.$this->config['FileName']));

            return $data;
        }

        return false;
    }

    protected function disabled()
    {
        $this->line("\n");
        $this->info('**** Scheduler is now disabled. ****');
        $this->line("\n");
    }

    protected function enabled()
    {
        $this->line("\n");
        $this->info('**** Scheduler is now Enabled. ****');
        $this->line("\n");
    }

    protected function alreadyEnabled()
    {
        $this->line("\n");
        $this->comment('**** Scheduler is already Enabled. ****');
        $this->line("\n");
    }

    protected function tryToEnable()
    {
        $this->line("\n");
        $this->error('**** WARNING: Task running is currently disabled. ****');
        $this->line("\n");
        $this->info('**** To re-enable tasks run: scheduler:enable ****');
        $this->line("\n");
    }
}
