<?php

declare(strict_types=1);

namespace Yuga\Providers;

use Yuga\Console\Commands\MakeRuntimeWorkerCommand;
use Yuga\Interfaces\Application\Application;
use Yuga\Providers\Shared\MakesCommandsTrait;

class RuntimeServiceProvider extends ServiceProvider
{
    use MakesCommandsTrait;

    public function load(Application $app)
    {
        if ($app->runningInConsole()) {
            $app->singleton(
                'command.runtime.worker',
                fn() => new MakeRuntimeWorkerCommand()
            );

            $this->commands('command.runtime.worker');
        }
    }

    public function boot() {}
}
