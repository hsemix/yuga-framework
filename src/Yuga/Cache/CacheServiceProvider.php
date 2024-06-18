<?php

namespace Yuga\Cache;

use Yuga\Support\FileSystem;
use Yuga\Providers\ServiceProvider;
use Yuga\Interfaces\Application\Application;
use Yuga\Providers\Shared\MakesCommandsTrait;

class CacheServiceProvider extends ServiceProvider
{
    use MakesCommandsTrait;

    public function load(Application $app)
    {
        $app->singleton('cache', function ($app) {
            FileSystem::createDir(storage('cache'));
            $cache = new CacheManager($app);
            $cache->registerDriver('cache.default', new FileCache(storage('cache')));
            $cache->setDefaultStoreName('cache.default');
            return $cache;
        });

        if ($app->runningInConsole()) {
            $app->singleton('command.cache.clear', function ($app) {
                return new Console\ClearCommand($app['cache'], $app['files']);
            });

            $app->singleton('command.cache.forget', function ($app) {
                return new Console\ForgetCommand($app['cache']);
            });

            $this->commands(['command.cache.clear', 'command.cache.forget']);
        }
    }
}