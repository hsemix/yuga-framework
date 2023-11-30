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
        
    }

    public function logErrorToFile($errorNumber, $errorString, $errorFile, $errorLine, $errorContext)
    {   
        $message = date("Y-m-d H:i:s - ");
        $message .= "Error: [" . $errorNumber ."], " . "$errorString in $errorFile on line $errorLine \r\n";
        // $message .= "\nVariables: " . print_r($errorContext, true) . "\r\n";
        // $this->app->registerProvider(EventServiceProvider::class);
        // $this->app['events']->dispatch('on:error');
        // echo $message;
        // die();
        $loggerFile = storage('logs/errors.log');
        if (!is_file($directory = storage('logs'))) {
            mkdir($directory, 0755, true);
        }
        \file_put_contents($loggerFile, $message, FILE_APPEND);
        // error_log($message, 3, $loggerFile);
        // die("There was a problem, please try again.<br />");
    }
}