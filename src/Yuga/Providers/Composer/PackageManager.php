<?php

namespace Yuga\Providers\Composer;

use Exception;
use Yuga\Support\Collection;
use Yuga\Support\FileSystem;

class PackageManager extends ModuleInstaller
{
    public function __construct(protected $basePath, protected $vendorDir, protected $providersPath)
    {
    }

    public function install()
    {
        $packages = [];

        if (FileSystem::exists($path = $this->vendorDir.DIRECTORY_SEPARATOR.'composer'.DIRECTORY_SEPARATOR.'installed.json')) {
            $installed = json_decode(FileSystem::read($path), true);

            $packages = $installed['packages'] ?? $installed;
        }

        $toBeIgnored = in_array('*', $ignore = $this->ignore());

        $this->write((new Collection($packages))->mapWithKeys(fn($package) => [$this->format($package['name']) => $package['extra']['yuga'] ?? []])->each(function ($configuration) use (&$ignore): void {
            $ignore = array_merge($ignore, $configuration['ignore'] ?? []);
        })->reject(fn($configuration, $package) => $toBeIgnored || in_array($package, $ignore))->filter()->all());
    }

    /**
     * Format the given package name.
     *
     * @param  string  $package
     * @return string
     */
    protected function format($package)
    {
        return str_replace($this->vendorDir.DIRECTORY_SEPARATOR, '', $package);
    }

    /**
     * Get all of the package names that should be ignored.
     *
     * @return array
     */
    protected function ignore()
    {
        if (!is_file($this->vendorDir.'composer.json')) {
            return [];
        }

        return json_decode(file_get_contents(
            $this->vendorDir.'composer.json'
        ), true)['extra']['yuga']['ignore'] ?? [];
    }

    /**
     * Write the given manifest array to disk.
     *
     * @param  array  $manifest
     * @return void
     *
     * @throws \Exception
     */
    protected function write(array $providersToInclude)
    {
        if (!is_writable($dirname = dirname((string) $this->providersPath))) {
            throw new Exception("The {$dirname} directory must be present and writable.");
        }

        $providers = include $this->providersPath;

        foreach ($providersToInclude as $moduleProviders) {
            foreach ($moduleProviders['providers'] as $moduleProvider) {
                if (!in_array($moduleProvider, $providers)) {
                    $providers[] = $moduleProvider;
                }
            }
        }

        $generatedProviders = '[';

        foreach ($providers as $provider) {
            $generatedProviders .= "\n\t\\" . $provider . "::class,";
        }

        $generatedProviders .= "\n]";
        
        file_put_contents(
            $this->providersPath,
            str_replace(
                '{providers}',
                $generatedProviders . ';',
                file_get_contents(__DIR__ . '/../Console/temps/config.temp')
            )
        );
    }
}