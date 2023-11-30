<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Route;

use Closure;
use Yuga\Http\Uri;
use Yuga\Http\Request;
use Yuga\Http\Redirect;
use Yuga\Http\Response;
use Yuga\Route\Support\IRoute;
use Yuga\Route\Router\RouteUrl;
use Yuga\Route\Router\RouteGroup;
use Yuga\Route\Router\RouteResource;
use Yuga\Exceptions\CallbackException;
use Yuga\Route\Router\RouteController;
use Yuga\Http\Middleware\BaseCsrfVerifier;
use Yuga\Http\Exceptions\BadFormedUrlException;

class Route
{
    /**
    * Default namespace added to all routes
    * @var string
    */
    protected static $defaultNamespace;
     
    /**
    * The response object
    * @var Response
    */
    protected static $response;

    /**
    * Router instance
    * @var Router
    */
    protected static $router;

    /**
    * Start/route request
    *
    * @throws HttpException
    * @throws NotFoundHttpException
    */
    public static function start()
    {
        static::router()->routeRequest();
    }
     
    /**
     * Route the given url to your callback on GET request method.
     *
     * @param string $url
     * @param string|\Closure $callback
     * @param array|null $settings
     * @return RouteUrl
     */
     public static function get($url, $callback, array $settings = null)
     {
         return static::match(['get'], $url, $callback, $settings);
     }    

    /**
     * Set default namespace which will be prepended to all routes.
     *
     * @param string $defaultNamespace
     */
     public static function setDefaultNamespace($defaultNamespace)
     {
         static::$defaultNamespace = $defaultNamespace;
     }

     /**
     * Prepends the default namespace to all new routes added.
     *
     * @param IRoute $route
     * @return IRoute
     */
    public static function addDefaultNamespace(IRoute $route)
    {
        if (static::$defaultNamespace !== null) {

            $callback = $route->getCallback();

            /* Only add default namespace on relative callbacks */
            if ($callback === null || $callback[0] !== '\\') {

                $namespace = static::$defaultNamespace;

                $currentNamespace = $route->getNamespace();

                if ($currentNamespace !== null) {
                    $namespace .= '\\' . $currentNamespace;
                }

                $route->setDefaultNamespace($namespace);

            }
        }

        return $route;
    }

    /**
     * Route the given url to your callback on POST request method.
     *
     * @param string $url
     * @param string|\Closure $callback
     * @param array|null $settings
     * @return RouteUrl
     */
    public static function post($url, $callback, array $settings = null)
    {
        return static::match(['post'], $url, $callback, $settings);
    }

    /**
     * Route the given url to your callback on PUT request method.
    *
    * @param string $url
    * @param string|\Closure $callback
    * @param array|null $settings
    * @return RouteUrl
    */
    public static function put($url, $callback, array $settings = null)
    {
        return static::match(['put'], $url, $callback, $settings);
    }

    /**
     * Route the given url to your callback on PATCH request method.
    *
    * @param string $url
    * @param string|\Closure $callback
    * @param array|null $settings
    * @return RouteUrl
    */
    public static function patch($url, $callback, array $settings = null)
    {
        return static::match(['patch'], $url, $callback, $settings);
    }

    /**
     * Route the given url to your callback on OPTIONS request method.
    *
    * @param string $url
    * @param string|\Closure $callback
    * @param array|null $settings
    * @return RouteUrl
    */
    public static function options($url, $callback, array $settings = null)
    {
        return static::match(['options'], $url, $callback, $settings);
    }

    /**
     * Route the given url to your callback on DELETE request method.
    *
    * @param string $url
    * @param string|\Closure $callback
    * @param array|null $settings
    * @return RouteUrl
    */
    public static function delete($url, $callback, array $settings = null)
    {
        return static::match(['delete'], $url, $callback, $settings);
    }

    /**
     * Groups allows for encapsulating routes with special settings.
    *
    * @param array $settings
    * @param \Closure $callback
    * @throws \InvalidArgumentException
    * @return RouteGroup
    */
    public static function group(array $settings, \Closure $callback)
    {
        $group = new RouteGroup();
        $group->setCallback($callback);
        $group->setSettings($settings);

        if (is_callable($callback) === false) {
            throw new \InvalidArgumentException('Invalid callback provided. Only functions or methods supported');
        }

        static::router()->addRoute($group);

        return $group;
    }

    /**
     * Alias for the form method
    *
    * @param string $url
    * @param callable $callback
    * @param array|null $settings
    * @see Route::form
    * @return RouteUrl
    */
    public static function basic($url, $callback, array $settings = null)
    {
        return static::match(['get', 'post'], $url, $callback, $settings);
    }

    /**
     * This type will route the given url to your callback on the provided request methods.
    * Route the given url to your callback on POST and GET request method.
    *
    * @param string $url
    * @param string|\Closure $callback
    * @param array|null $settings
    * @see Route::form
    * @return RouteUrl
    */
    public static function form($url, $callback, array $settings = null)
    {
        return static::match(['get', 'post'], $url, $callback, $settings);
    }

    /**
     * This type will route the given url to your callback on the provided request methods.
    *
    * @param array $requestMethods
    * @param string $url
    * @param string|\Closure $callback
    * @param array|null $settings
    * @return RouteUrl|IRoute
    */
    public static function match(array $requestMethods, $url, $callback, array $settings = null)
    {
        $route = new RouteUrl($url, $callback);
        $route->setRequestMethods($requestMethods);
        $route = static::addDefaultNamespace($route);

        if ($settings !== null) {
            $route->setSettings($settings);
        }

        static::router()->addRoute($route);

        return $route;
    }

    /**
     * This type will route the given url to your callback and allow any type of request method
    *
    * @param string $url
    * @param string|\Closure $callback
    * @param array|null $settings
    * @return RouteUrl|IRoute
    */
    public static function all($url, $callback, array $settings = null)
    {
        $route = new RouteUrl($url, $callback);
        $route = static::addDefaultNamespace($route);

        if ($settings !== null) {
            $route->setSettings($settings);
        }

        static::router()->addRoute($route);

        return $route;
    }

    /**
     * This route will route request from the given url to the controller.
    *
    * @param string $url
    * @param string $controller
    * @param array|null $settings
    * @return RouteController|IRoute
    */
    public static function controller($url, $controller, array $settings = null)
    {
        $route = new RouteController($url, $controller);
        $route = static::addDefaultNamespace($route);

        if ($settings !== null) {
            $route->setSettings($settings);
        }

        static::router()->addRoute($route);

        return $route;
    }

     /**
     * Add exception callback handler.
     *
     * @param \Closure $callback
     * @return CallbackException $callbackException
     */
    public static function error(Closure $callback)
    {
        $routes = static::router()->getRoutes();

        $callbackException = new CallbackException($callback);

        $group = new RouteGroup();
        $group->addExceptionHandler($callbackException);

        array_unshift($routes, $group);

        static::router()->setRoutes($routes);

        return $callbackException;
    }

    public static function getDefaultNamespace()
    {
        return static::$defaultNamespace;
    }
 
    /**
     * This type will route all REST-supported requests to different methods in the provided controller.
    *
    * @param string $url
    * @param string $controller
    * @param array|null $settings
    * @return RouteResource|IRoute
    */
    public static function resource($url, $controller, array $settings = null)
    {
        $route = new RouteResource($url, $controller);
        $route = static::addDefaultNamespace($route);

        if ($settings !== null) {
            $route->setSettings($settings);
        }

        static::router()->addRoute($route);

        return $route;
    }
    /**
    * Returns the router instance
    *
    * @return Router
    */
    public static function router()
    {
        if (static::$router === null) {
            static::$router = new Router();
        }

        return static::$router;
    }
    /**
    * Base CSRF verifier
    *
    * @param BaseCsrfVerifier $baseCsrfVerifier
    */
    public static function csrfVerifier(BaseCsrfVerifier $baseCsrfVerifier)
    {
        static::router()->setCsrfVerifier($baseCsrfVerifier);
    }

    public static function getUrl($name = null, $parameters = null, $getParams = null)
    {
        // return static::router()->getUrl($name, $parameters, $getParams);
        try {
            return static::router()->getUrl($name, $parameters, $getParams);
        } catch (\Exception $e) {
            try {
                return new Uri('/');
            } catch (BadFormedUrlException $e) {

            }
        }
        return null;
    }

    /**
    * Get the request
    *
    * @return \Yuga\Http\Request
    */
    public static function request()
    {
        return static::router()->getRequest();
    }

    /**
    * Get the response object
    *
    * @return Response
    */
    public static function response()
    {
        if (static::$response === null) {
            static::$response = new Response(static::request(), new Redirect(static::request()));
        }

        return static::$response;
    }
}