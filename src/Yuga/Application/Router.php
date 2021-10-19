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

class Routers extends Route
{
    public function __construct()
    {
        $this->init();
        parent::start();
    }

    public static function init()
    {
        // Load routes/web.php
        // parent::csrfVerifier(new Token());
        // static::group(['namespace' => env('APP_NAMESPACE', 'App').'\Controllers', 'exceptionHandler' => NotFoundHttpExceptionHandler::class], function () {
        //     require_once env('base_path') . DIRECTORY_SEPARATOR. 'routes' . DIRECTORY_SEPARATOR . 'web.php';

        //     if (env('ENABLE_MVP_ROUTES', false)) {
        //         $routePrefix = '/' . trim(env('PREFIX_MVP_ROUTE', '/'), '/') . '/';
        //         $routePrefix = ($routePrefix == '//') ? '/' : $routePrefix;
        //         $controller = env('MVP_CONTROLLER', 'Controller');
        //         if (env('MATCH_ROUTES_TO_CONTROLLERS', false)) {
        //             trigger_error("MVP ROUTING and IMPLICIT ROUTING can not co-exist", E_USER_WARNING);
        //         }
        //         static::all($routePrefix . '{slug?}', $controller . '@show')->where(['slug' => '(.*)']);
        //     }
            
        // });
    }
}