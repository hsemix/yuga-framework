<?php
namespace Yuga\Providers;

use Yuga\Console\Commands\ServeCommand;
use Yuga\Events\Console\MakeEventCommand;
use Yuga\Models\Console\MakeModelCommand;
use Yuga\View\Console\MakeViewModelCommand;
use Yuga\Http\Console\MakeMiddlewareCommand;
use Yuga\Interfaces\Application\Application;
use Yuga\Authenticate\Console\MakeAuthCommand;
use Yuga\Database\Console\MakeMigrationCommand;
use Yuga\Events\Console\MakeEventHandlerCommand;
use Yuga\Database\Console\MakeMigrationUpCommand;
use Yuga\Controllers\Console\MakeControllerCommand;
use Yuga\Database\Console\MakeMigrationDownCommand;
use Yuga\Database\Console\MakeDatabaseBackupCommand;
use Yuga\Providers\Console\MakeServiceProviderCommand;

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
        // 'MakeDatabaseBackup'    => 'yuga.command.backup',
        'MigrationUp'           => 'yuga.command.up',
        'MigrationMake'         => 'yuga.command.make',
        'MakeServiceProvider'   => 'yuga.command.provider.make',
        'MakeEvent'             => 'yuga.command.event.make',
        'MakeEventHandler'      => 'yuga.command.event.handler.make',
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

            call_user_func_array(array($this, $method), [$command, $app]);
        }

        $this->commands(array_values($this->commands));
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
            return new MakeDatabaseBackupCommand;
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
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_values($this->commands);
    }
}
