<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Shared;

use Yuga\App;
use Yuga\Hash\Hash;
use Yuga\Views\View;
use Yuga\Support\Str;
use Yuga\Http\Request;
use Yuga\Cookie\Cookie;
use Yuga\Http\Redirect;
use Yuga\Http\Response;
use Yuga\View\ViewModel;
use Yuga\Session\Session;
use Yuga\Validate\Validate;
use Yuga\Http\Middleware\MiddleWare;
use Yuga\Http\Middleware\IMiddleware;
use Yuga\Route\Exceptions\HttpException;
use Yuga\Route\Exceptions\NotFoundHttpException;

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
        $wares = array_merge($wares, require path('config/AppMiddleWare.php'));
        if ($except) {
            $middleWare->except = $except;
        }
        
        $request = request();
        $ware = (array) $ware;

        if (is_null($except)) $except = [];

        if (in_array(ltrim(request()->getUri(), '/'), $except)) return;

        $next = function ($next) use ($middleWare, $request) {
            $result = $middleWare->run($request, function ($next) {
                return $next;
            });
            
            if ($result instanceof ViewModel || is_string($result) || $result instanceof View || is_scalar($result)) {
                echo $result;
            } elseif ($result instanceof Redirect) {
                if ($result->getPath() !== null) {
                    $result->header('Location: ' . $result->getPath());
                    exit();
                } else {
                    throw new NotFoundHttpException("You have not provided a Redirect URL");
                }
            }
        };
        
        foreach ($ware as $controllerMiddleware) {
            if (isset($wares[$controllerMiddleware])) {
                $routeMiddleWare = App::resolve($wares[$controllerMiddleware]);
                
                if (($routeMiddleWare instanceof IMiddleware) === false) {
                    throw new HttpException($controllerMiddleware . ' must inherit the IMiddleware interface');
                }
                // $result = $routeMiddleWare->run($request, function($request) {
                //     return $request;
                // }, $middleWare->except);

                // if ($result instanceof ViewModel || is_string($result) || $result instanceof View ) {
                //     echo $result;
                // } elseif ($result instanceof Redirect) {
                //     if ($result->getPath() !== null) {
                //         $result->header('Location: ' . $result->getPath());
                //         exit();
                //     } else {
                //         throw new NotFoundHttpException("You have not provided a Redirect URL");
                //     }
                // }

                $next = function ($request) use ($routeMiddleWare, $next) {
                    $result = $routeMiddleWare->run($request, $next);

                    if ($result instanceof ViewModel || is_string($result) || $result instanceof View || is_scalar($result)) {
                        echo $result;
                    } elseif ($result instanceof Redirect) {
                        if ($result->getPath() !== null) {
                            $result->header('Location: ' . $result->getPath());
                            exit();
                        } else {
                            throw new NotFoundHttpException("You have not provided a Redirect URL");
                        }
                    } 
                };

                // return;
            } else {
                throw new HttpException($controllerMiddleware . ' Middleware is not yet defined');
            }
        }
        
        return $next($request);
    }
    
    public function getView()
    {
        return $this->view = App::make('view');
    }

    /**
     * Route every uri to resources/views/Page/Route.php
     * 
     * @param Request $request
     * @param string|null $slug
     * 
     * @return View
     */
    public function show(Request $request, $slug = null): View
    {
        $segments = [];
        $slug = trim($slug, '/');

        if (!empty($slug)) {
            $segments = explode('/', $slug, 2);
        }

        // Compute the page and subpage.
        list ($page, $subPage) = array_pad($segments, 2, null);

        // Compute the full View name, i.e. 'about-us' -> 'Pages/Users'
        array_unshift($segments, 'pages');

        $view = implode('/', array_map(function ($value) {
            return Str::studly($value);
        }, $segments));

        $view = rtrim($view, '/');

        if (View::exists($view)) {
            // We found a proper View for the given URI.
        }

        // We will look for a Home View before going to Exception.
        else if (!View::exists($viewFile = $view .'/Home')) {
            throw new NotFoundHttpException('no template file "' . $viewFile . '.php" or "' . $view . '.php"  present in directory "./resources/views"');
        }

        $title = Str::title(str_replace(array('-', '_'), ' ', $subPage ?: ($page ?: 'Home')));
    
        $methods = explode('/', $slug);
        $methodCamel = implode('', array_map(function ($value) {
            return Str::studly($value);
        }, $methods));

        $method_snake = Str::deCamelize($methodCamel);

        $requestMethod = strtolower($request->getMethod());
        if ($requestMethod != 'get') {
            if ($requestMethod == 'post') {
                if (method_exists($this, "on" . ucfirst($requestMethod) . $methodCamel)) {
                    App::call([$this, "on" . ucfirst($requestMethod) . $methodCamel]);
                } elseif (method_exists($this, "on_" . $requestMethod . '_' . $method_snake)) {
                    App::call([$this, "on_" . $requestMethod . '_' . $method_snake]);
                }
            } else {
                if (method_exists($this, "onPost" . ucfirst($requestMethod) . $methodCamel)) {
                    App::call([$this, "onPost" . ucfirst($requestMethod) . $methodCamel]);
                } elseif (method_exists($this, "on_post_" . $requestMethod . '_' . $method_snake)) {
                    App::call([$this, "on_post_" . $requestMethod . '_' . $method_snake]);
                }
            }
            
        } else {
            if (method_exists($this, 'render' . $methodCamel)) {
                App::call([$this, 'render' . $methodCamel]);
            } elseif (method_exists($this, 'render_' . $method_snake)) {
                App::call([$this, 'render_' . $method_snake]);
            }
        }
        
        return View::make($view)->shares('title', $title);
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
