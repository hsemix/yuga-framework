<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga;

use Yuga\Route\Route;
use Yuga\Http\Middleware\BaseCsrfVerifier as Token;

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
        static::group(['namespace' => env('APP_NAMESPACE', 'App').'\Controllers'], function () {
            require_once env('base_path') . DIRECTORY_SEPARATOR. 'routes' . DIRECTORY_SEPARATOR . 'web.php';
        });
    }
}