<?php

namespace Yuga\Providers\Composer;

use Composer\Script\Event;
use Yuga\Application\Application;

class ModuleInstaller
{
    /**
     * Handle the post-install Composer event.
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function postInstall(Event $event)
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';

        static::houseKeeping();
    }

    /**
     * Handle the post-update Composer event.
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function postUpdate(Event $event)
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';

        static::houseKeeping();
    }

    /**
     * Handle the post-autoload-dump Composer event.
     *
     * @param  \Composer\Script\Event  $event
     * @return void
     */
    public static function postAutoloadDump(Event $event)
    {
        require_once $event->getComposer()->getConfig()->get('vendor-dir').'/autoload.php';

        static::houseKeeping();
    }

    /**
     * Clear the cached Yuga bootstrapping files.
     *
     * @return void
     */
    protected static function houseKeeping()
    {
        $yuga = new Application(getcwd());

        if (is_file($configPath = $yuga->getCachedConfigPath())) {
            @unlink($configPath);
        }

        if (is_file($servicesPath = $yuga->getCachedServicesPath())) {
            @unlink($servicesPath);
        }

        if (is_file($packagesPath = $yuga->getCachedPackagesPath())) {
            @unlink($packagesPath);
        }
    }
}
