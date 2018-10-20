<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Application;

use Exception;
use Yuga\Route\Route;
use Yuga\Http\Request;
use Yuga\Route\Router as AppRouter;
use Yuga\Route\Support\IRouterBootManager;
use Yuga\Http\Middleware\BaseCsrfVerifier as Token;
use Yuga\Route\Exceptions\NotFoundHttpExceptionHandler;

class Router extends Route
{
    public function __construct()
    {
        $this->init();
        parent::start();
    }
    
    public static function init()
    {
        // Load routes/web.php
        parent::csrfVerifier(new Token());
        static::group(['namespace' => env('APP_NAMESPACE', 'App').'\Controllers', 'exceptionHandler' => NotFoundHttpExceptionHandler::class], function () {
            require_once env('base_path') . DIRECTORY_SEPARATOR. 'routes' . DIRECTORY_SEPARATOR . 'web.php';
        });
    }
}