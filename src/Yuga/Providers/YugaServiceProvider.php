<?php

namespace Yuga\Providers;

use Yuga\Console\Commands\ServeCommand;
use Yuga\Console\Commands\MakeAppCommand;
use Yuga\Events\Console\MakeEventCommand;
use Yuga\Models\Console\MakeModelCommand;
use Yuga\Scheduler\Console\MakeRunCommand;
use Yuga\Queue\Console\MakeQueueJobCommand;
use Yuga\View\Console\MakeViewModelCommand;
use Yuga\Http\Console\MakeMiddlewareCommand;
use Yuga\Interfaces\Application\Application;
use Yuga\Models\Console\MakeScaffoldCommand;
use Yuga\Queue\Console\MakeQueueWorkCommand;
use Yuga\Queue\Console\MakeQueueFlushCommand;
use Yuga\Queue\Console\MakeQueueRetryCommand;
use Yuga\Queue\Console\MakeQueueTableCommand;
use Yuga\Scheduler\Console\MakeEnableCommand;
use Yuga\Scheduler\Console\MakeListerCommand;
use Yuga\Authenticate\Console\MakeAuthCommand;
use Yuga\Queue\Console\MakeQueueForgetCommand;
use Yuga\Scheduler\Console\MakeDisableCommand;
use Yuga\Scheduler\Console\MakePublishCommand;
use Yuga\Database\Console\MakeMigrationCommand;
use Yuga\Queue\Console\MakeQueueMonitorCommand;
use Yuga\Queue\Console\MakeQueueRestartCommand;
use Yuga\Events\Console\MakeEventHandlerCommand;
use Yuga\Database\Console\MakeMigrationUpCommand;
use Yuga\Providers\Console\PackagePublishCommand;
use Yuga\Controllers\Console\MakeControllerCommand;
use Yuga\Database\Console\MakeMigrationDownCommand;
use Yuga\Database\Console\MakeMigrationSeedCommand;
use Yuga\Database\Console\MakeDatabaseBackupCommand;
use Yuga\Database\Console\MakeDatabaseRestoreCommand;
use Yuga\Providers\Console\MakeServiceProviderCommand;
use Yuga\Providers\Composer\Console\DiscoverPackageCommand;

class YugaServiceProvider extends ServiceProvider
{
    protected $app;
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'MigrationDown'         => 'yuga.command.down',
        'Serve'                 => 'yuga.command.serve',
        'MakeAuth'              => 'yuga.command.auth.make',
        'MakeMiddleware'        => 'yuga.command.middleware.make',
        'MakeModel'             => 'yuga.command.model',
        'MakeController'        => 'yuga.command.controller',
        'MakeViewModel'         => 'yuga.command.viewmodel',
        'MakeDatabaseBackup'    => 'yuga.command.backup',
        'MakeDatabaseRestore'   => 'yuga.command.restore',
        'MigrationUp'           => 'yuga.command.up',
        'MigrationMake'         => 'yuga.command.make',
        'MakeServiceProvider'   => 'yuga.command.provider.make',
        'MakeEvent'             => 'yuga.command.event.make',
        'MakeEventHandler'      => 'yuga.command.event.handler.make',
        'MigrationSeed'         => 'yuga.command.seed',
        'Scaffold'              => 'yuga.command.scaffold',
        'MakeApp'               => 'yuga.command.appcommand',
        'MakeSchedulerEnable'   => 'yuga.command.scheduler.enable',
        'MakeSchedulerDisable'  => 'yuga.command.scheduler.disable',
        'MakeSchedulerLister'   => 'yuga.command.scheduler.lister',
        'MakeSchedulerRun'      => 'yuga.command.scheduler.run',
        'MakeSchedulerPublish'  => 'yuga.command.scheduler.publish',
        'MakeQueueTable'        => 'yuga.command.queue.table.make',
        'MakeQueueWork'         => 'yuga.command.queue.work.make',
        'MakeQueueFlush'        => 'yuga.command.queue.flush.make',
        'MakeQueueForget'       => 'yuga.command.queue.forget.make',
        'MakeQueueMonitor'      => 'yuga.command.queue.monitor.make',
        'MakeQueueRestart'      => 'yuga.command.queue.restart.make',
        'MakeQueueRetry'        => 'yuga.command.queue.retry.make',
        'MakeQueueJob'          => 'yuga.command.queue.job.make',
        'DiscoverPackage'       => 'yuga.command.module.discover',
        'PackagePublish'       => 'yuga.command.modules.publish',
    ];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function load(Application $app)
    {
        foreach ($this->commands as $name => $command) {
            $method = "register{$name}Command";

            call_user_func_array([$this, $method], [$command, $app]);
        }

        $otherCommands = [];
        if (\file_exists(path('config/AppCommands.php'))) {
            $otherCommands = require path('config/AppCommands.php');
        }

        if (count($otherCommands) > 0) {
            foreach ($otherCommands as $command) {
                $app->singleton($command, function () use ($command, $app) {
                    return $app->resolve($command);
                });
            }
        }

        $this->commands(array_values(array_merge($this->commands, $otherCommands)));
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMakeMiddlewareCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeMiddlewareCommand;
        });
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerServeCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new ServeCommand;
        });
    }

    /**
     * Make Auth command i.e all classes responsible for the authentication of a user (login, register, forgot password, remember me, reset password)
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMakeAuthCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeAuthCommand;
        });
    }

    /**
     * Make Model command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMakeModelCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeModelCommand;
        });
    }

    /**
     * Make Controller command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMakeControllerCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeControllerCommand;
        });
    }

    /**
     * Make ViewModel command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMakeViewModelCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeViewModelCommand;
        });
    }

    /**
     * Make Database Backup command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMakeDatabaseBackupCommand($command, $app)
    {
        $app->singleton($command, function () {
            return $this->app->resolve(MakeDatabaseBackupCommand::class);
        });
    }

    /**
     * Make Database Backup command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMakeDatabaseRestoreCommand($command, $app)
    {
        $app->singleton($command, function () {
            return $this->app->resolve(MakeDatabaseRestoreCommand::class);
        });
    }

    /**
     * Make Migration Up command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMigrationUpCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeMigrationUpCommand;
        });
    }

    /**
     * Make Migration Down command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMigrationDownCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeMigrationDownCommand;
        });
    }

    /**
     * Make Migration command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMigrationMakeCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeMigrationCommand;
        });
    }

    /**
     * Make ServiceProvider command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMakeServiceProviderCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeServiceProviderCommand;
        });
    }

    /**
     * Make event command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMakeEventCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeEventCommand;
        });
    }

    /**
     * Make event handler command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMakeEventHandlerCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeEventHandlerCommand;
        });
    }

    /**
     * Migration seed command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMigrationSeedCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeMigrationSeedCommand;
        });
    }

    /**
     * Scaffold command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerScaffoldCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeScaffoldCommand;
        });
    }

    /**
     * App commands
     */
    protected function registerMakeAppCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeAppCommand;
        });
    }

    protected function registerMakeSchedulerEnableCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeEnableCommand;
        });
    }

    protected function registerMakeSchedulerDisableCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeDisableCommand;
        });
    }

    protected function registerMakeSchedulerListerCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeListerCommand;
        });
    }

    protected function registerMakeSchedulerRunCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeRunCommand;
        });
    }

    protected function registerMakeSchedulerPublishCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakePublishCommand;
        });
    }

    protected function registerMakeQueueTableCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeQueueTableCommand;
        });
    }

    protected function registerMakeQueueWorkCommand($command, $app)
    {
        $app->singleton($command, MakeQueueWorkCommand::class);
        $app->resolve($command);
    }


    protected function registerMakeQueueFlushCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeQueueFlushCommand;
        });
    }

    protected function registerMakeQueueForgetCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeQueueForgetCommand;
        });
    }

    protected function registerMakeQueueMonitorCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeQueueMonitorCommand;
        });
    }

    protected function registerMakeQueueRestartCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeQueueRestartCommand;
        });
    }

    protected function registerMakeQueueRetryCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeQueueRetryCommand;
        });
    }

    protected function registerMakeQueueJobCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new MakeQueueJobCommand;
        });
    }

    /**
     * Discover All Yuga Packages and register them as services
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerDiscoverPackageCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new DiscoverPackageCommand;
        });
    }

    /**
     * Publish all vendor assets that belong to a package to their respective directories or locations
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerPackagePublishCommand($command, $app)
    {
        $app->singleton($command, function () {
            return new PackagePublishCommand;
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_values($this->commands);
    }
}
