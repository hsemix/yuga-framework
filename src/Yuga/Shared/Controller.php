<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Shared;

use Yuga\App;
use Yuga\Hash\Hash;
use Yuga\Http\Request;
use Yuga\Cookie\Cookie;
use Yuga\Http\Response;
use Yuga\Session\Session;
use Yuga\Validate\Validate;
use Yuga\Http\Middleware\MiddleWare;
use Yuga\Http\Middleware\IMiddleware;
use Yuga\Route\Exceptions\HttpException;

trait Controller
{
    public $app;
    public $hash;
    public $view;
    public $cookie;
    public $request;
    public $session;
    public $validate;
    public $response; 
    public $login_id;
    

    public function getApp()
    {
        return $this->app = App::getInstance();
    }
    public function getHash()
    {
        return $this->hash = App::resolve(Hash::class);
    }

    public function getCookie()
    {
        return $this->cookie = App::resolve(Cookie::class);
    }

    
    public function getRequest()
    {
        return $this->request = App::resolve(Request::class);
    }

    public function getSession()
    {
        return $this->session = App::resolve(Session::class);
    }
    public function getResponse()
    {
        return $this->response = App::resolve(Response::class);; 
    }

    public function getValidator()
    {
        return $this->validate = App::resolve(Validate::class);
    }

    public function getLoggedInUserId()
    {
        $this->login_id = $this->getSession()->getUserId();
    }
    /**
     * Run Controller method or contructor specific middleware
     * 
     * @param string | array $ware
     * @param array | null $except
     * 
     * @return \Yuga\Http\Middleware\MiddleWare
     */
    public function middleWare($ware, array $except = null)
    {
        $middleWare = new MiddleWare();
        $wares = $middleWare->routerMiddleWare;
        $wares = array_merge($wares, require path('config/AppMiddleware.php'));
        if ($except) {
            $middleWare->except = $except;
        }
        if (is_array($ware)) {
            foreach ($ware as $controllerMiddleware) {
                if (isset($wares[$controllerMiddleware])) {
                    $routeMiddleWare = App::resolve($wares[$controllerMiddleware]);
                    $request = request();
                    if (($routeMiddleWare instanceof IMiddleware) === false) {
                        throw new HttpException($controllerMiddleware . ' must inherit the IMiddleware interface');
                    }
                    $results = $routeMiddleWare->run($request, function($request) {
                        return $request;
                    }, $middleWare->except);
                } else {
                    throw new HttpException($controllerMiddleware . ' Middleware is not yet defined');
                }
            }
        } else {
            $routeMiddleWare = App::resolve($wares[$ware]);
            $request = request();
            if (($routeMiddleWare instanceof IMiddleware) === false) {
                throw new HttpException($ware . ' must inherit the IMiddleware interface');
            }
            $results = $routeMiddleWare->run($request, function($request) {
                return $request;
            }, $middleWare->except);
        }
		
        return $middleWare;
    }
    
    public function getView()
    {
        return $this->view = App::make('view');
    }

    /**
     * Initialize all controller defaults
     * 
     * @param null
     * 
     * @return void
     */
    protected function init()
    {
        $this->getApp();
        $this->getHash();
        $this->getView();
        $this->getCookie();
        $this->getRequest();
        $this->getSession();
        $this->getResponse();
        $this->getValidator();
        $this->getLoggedInUserId();
    }
}