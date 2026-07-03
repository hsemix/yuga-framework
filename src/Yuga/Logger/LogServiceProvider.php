<?php

namespace Yuga\Logger;

use Yuga\Providers\ServiceProvider;
use Yuga\Events\EventServiceProvider;
use Yuga\Interfaces\Application\Application;

class LogServiceProvider extends ServiceProvider
{
    protected $app;

    protected static $publishes = [
        'Storage.php' => 'app/Storage.php',
    ];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function load(Application $app)
    {
        $app->singleton('logger', function () {
            return new \Yuga\Logger\LogManager($this->app);
        });
    }

    public function logErrorToFile($errorNumber, $errorString, $errorFile, $errorLine)
    {
        logger('errors')->error($errorString, [
            'number' => $errorNumber,
            'file' => $errorFile,
            'line' => $errorLine,
        ]);
    }
}
