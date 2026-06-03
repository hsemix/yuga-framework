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
                $app->singleton($command, fn() => $app->resolve($command));
            }
        }

        $this->commands(array_values(array_merge($this->commands, $otherCommands)));
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerMakeMiddlewareCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeMiddlewareCommand);
    }

    /**
     * Register the command.
     *
     * @return void
     */
    protected function registerServeCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new ServeCommand);
    }

    /**
     * Make Auth command i.e all classes responsible for the authentication of a user (login, register, forgot password, remember me, reset password)
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMakeAuthCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeAuthCommand);
    }

    /**
     * Make Model command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMakeModelCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeModelCommand);
    }

    /**
     * Make Controller command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMakeControllerCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeControllerCommand);
    }

    /**
     * Make ViewModel command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMakeViewModelCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeViewModelCommand);
    }

    /**
     * Make Database Backup command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMakeDatabaseBackupCommand(string $command, $app)
    {
        $app->singleton($command, fn() => $this->app->resolve(MakeDatabaseBackupCommand::class));
    }

    /**
     * Make Database Backup command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMakeDatabaseRestoreCommand(string $command, $app)
    {
        $app->singleton($command, fn() => $this->app->resolve(MakeDatabaseRestoreCommand::class));
    }

    /**
     * Make Migration Up command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMigrationUpCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeMigrationUpCommand);
    }

    /**
     * Make Migration Down command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMigrationDownCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeMigrationDownCommand);
    }

    /**
     * Make Migration command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMigrationMakeCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeMigrationCommand);
    }

    /**
     * Make ServiceProvider command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMakeServiceProviderCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeServiceProviderCommand);
    }

    /**
     * Make event command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMakeEventCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeEventCommand);
    }

    /**
     * Make event handler command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMakeEventHandlerCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeEventHandlerCommand);
    }

    /**
     * Migration seed command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerMigrationSeedCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeMigrationSeedCommand);
    }

    /**
     * Scaffold command
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerScaffoldCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeScaffoldCommand);
    }

    /**
     * App commands
     */
    protected function registerMakeAppCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeAppCommand);
    }

    protected function registerMakeSchedulerEnableCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeEnableCommand);
    }

    protected function registerMakeSchedulerDisableCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeDisableCommand);
    }

    protected function registerMakeSchedulerListerCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeListerCommand);
    }

    protected function registerMakeSchedulerRunCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeRunCommand);
    }

    protected function registerMakeSchedulerPublishCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakePublishCommand);
    }

    protected function registerMakeQueueTableCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeQueueTableCommand);
    }

    protected function registerMakeQueueWorkCommand(string $command, $app)
    {
        $app->singleton($command, MakeQueueWorkCommand::class);
        $app->resolve($command);
    }


    protected function registerMakeQueueFlushCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeQueueFlushCommand);
    }

    protected function registerMakeQueueForgetCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeQueueForgetCommand);
    }

    protected function registerMakeQueueMonitorCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeQueueMonitorCommand);
    }

    protected function registerMakeQueueRestartCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeQueueRestartCommand);
    }

    protected function registerMakeQueueRetryCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeQueueRetryCommand);
    }

    protected function registerMakeQueueJobCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new MakeQueueJobCommand);
    }

    /**
     * Discover All Yuga Packages and register them as services
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerDiscoverPackageCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new DiscoverPackageCommand);
    }

    /**
     * Publish all vendor assets that belong to a package to their respective directories or locations
     * 
     * @param string command
     * @param Application $app
     * 
     * @return void
     */
    protected function registerPackagePublishCommand(string $command, $app)
    {
        $app->singleton($command, fn() => new PackagePublishCommand);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    #[\Override]
    public function provides()
    {
        return array_values($this->commands);
    }
}
