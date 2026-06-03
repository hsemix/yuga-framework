<?php

/**
 * @author Mahad Tech Solutions
 */

namespace Yuga\Route;

use Exception;
use ReflectionClass;
use Yuga\Views\View;
use Yuga\Support\Str;
use Yuga\Http\Request;
use Yuga\Http\Redirect;
use Yuga\View\ViewModel;
use Yuga\Route\Shared\Shared;
use Yuga\Route\Support\IRoute;
use Yuga\Exceptions\IException;
use Yuga\Application\Application;
use Yuga\Route\Support\IGroupRoute;
use Yuga\Handlers\IExceptionHandler;
use Yuga\Route\Support\ILoadableRoute;
use Yuga\Route\Exceptions\HttpException;
use Yuga\Route\Support\IControllerRoute;
use Yuga\Http\Middleware\BaseCsrfVerifier;
use Yuga\Route\Support\IRouterBootManager;
use Yuga\Route\Exceptions\NotFoundHttpException;
use Yuga\Route\Exceptions\NotFoundHttpMethodException;
use Yuga\Route\Exceptions\NotFoundHttpControllerException;

class Router
{
    use Shared;
    /**
     * The instance of this class
     * @var static
     */
    protected static $instance;

    /**
     * Current request
     * @var Request
     */
    protected $request;

    /**
     * Defines if a route is currently being processed.
     * @var bool
     */
    protected $processingRoute;

    /**
     * All added routes
     * @var array
     */
    protected $routes;

    /**
     * List of processed routes
     * @var array
     */
    protected $processedRoutes;

    /**
     * Stack of routes used to keep track of sub-routes added
     * when a route is being processed.
     * @var array
     */
    protected $routeStack;

    /**
     * List of added bootmanagers
     * @var array
     */
    protected $bootManagers;

    /**
     * Csrf verifier class
     * @var BaseCsrfVerifier
     */
    protected $csrfVerifier;

    /**
     * Get exception handlers
     * @var array
     */
    protected $exceptionHandlers;

    /**
     * Get default router
     * 
     * @var array
     */
    protected $defaultRouteCollection = [
        'controller' => 'Home',
        'method' => 'index',
        'params' => [],
    ];

    protected bool $routesLoaded = false;

    public function __construct()
    {
        $this->reset();
    }

    public function reset()
    {
        $this->processingRoute = false;
        $this->request = new Request();
        $this->routes = [];
        $this->bootManagers = [];
        $this->routeStack = [];
        $this->processedRoutes = [];
        $this->exceptionHandlers = [];
        $defaultRoutes = env('ROUTE_DEFAULTS', json_encode(['controller' => 'Home', 'method' => 'index']));
        $defaultRoutes = is_array($defaultRoutes) ? $defaultRoutes : json_decode((string) $defaultRoutes, true);
        $this->defaultRouteCollection['controller'] = $defaultRoutes['controller'];
        $this->defaultRouteCollection['method'] = $defaultRoutes['method'];
    }

    public function prepareForRequest(Request $request): self
    {
        $this->request = $request;
        $this->processingRoute = false;
        $this->routeStack = [];

        if (method_exists($this->request, 'clearLoadedRoute')) {
            $this->request->clearLoadedRoute();
        }

        return $this;
    }

    /**
     * Add route
     * @return IRoute
     */
    public function addRoute(IRoute $route)
    {
        /*
         * If a route is currently being processed, that means that the
         * route being added are rendered from the parent routes callback,
         * so we add them to the stack instead.
         */
        if ($this->processingRoute === true) {
            $this->routeStack[] = $route;
        } else {
            $this->routes[] = $route;
        }

        return $route;
    }

    /**
     * Process added routes.
     *
     * @throws NotFoundHttpException
     */
    protected function processRoutes(array $routes, ?IGroupRoute $group = null, ?IRoute $parent = null)
    {
        // Loop through each route-request
        $max = count($routes) - 1;

        $exceptionHandlers = [];

        $url = $this->request->getRewriteUrl() ?? $this->request->getUri();
        /* @var $route IRoute */
        for ($i = $max; $i >= 0; $i--) {

            $route = $routes[$i];

            /* @var $route IGroupRoute */
            if ($route instanceof IGroupRoute) {

                $group = $route;

                $this->processingRoute = true;
                $route->renderRoute($this->request);
                $this->processingRoute = false;

                /* Add exception handlers */
                if ($route->matchRoute($url, $this->request) === true && count($route->getExceptionHandlers()) > 0) {

                    /** @noinspection AdditionOperationOnArraysInspection */
                    $exceptionHandlers += $route->getExceptionHandlers();
                }
            }

            if ($group instanceof \Yuga\Route\Support\IGroupRoute) {

                /* Add the parent group */
                $route->setGroup($group);
            }

            if ($parent instanceof \Yuga\Route\Support\IRoute) {

                /* Add the parent route */
                $route->setParent($parent);

                /* Add/merge parent settings with child */
                $route->setSettings($parent->toArray(), true);
            }

            if ($route instanceof ILoadableRoute) {

                /* Add the route to the map, so we can find the active one when all routes has been loaded */
                $this->processedRoutes[] = $route;
            }

            if (count($this->routeStack) > 0) {

                /* Pop and grab the routes added when executing group callback earlier */
                $stack = $this->routeStack;
                $this->routeStack = [];

                /* Route any routes added to the stack */
                $this->processRoutes($stack, $route, $group);
            }
        }

        $this->exceptionHandlers = array_merge($exceptionHandlers, $this->exceptionHandlers);
    }

    /**
     * Load routes
     * @throws NotFoundHttpException
     * @return void
     */
    public function loadRoutes()
    {
        if (count($this->processedRoutes) > 0) {
            return;
        }
        /* Initialize boot-managers */
        if (count($this->bootManagers) > 0) {

            $max = count($this->bootManagers) - 1;

            /* @var $manager IRouterBootManager */
            for ($i = $max; $i >= 0; $i--) {
                $manager = $this->bootManagers[$i];
                $manager->boot($this->request);
            }
        }

        /* Loop through each route-request */
        $this->processRoutes($this->routes);
    }

    public function ensureRoutesLoaded(): void
    {
        if ($this->routesLoaded === true || count($this->processedRoutes) > 0) {
            return;
        }

        if ($this->routesAreCached()) {
            $this->loadCachedRoutes();
            $this->routesLoaded = true;
            return;
        }

        $this->loadRoutes();

        $this->routesLoaded = true;

        if (config('routing.cache', false) && !$this->routesAreCached()) {
            $this->cacheRoutes();
        }
    }

    public function routesAreCached(): bool
    {
        $path = config('routing.cache_path');

        return is_file($path);
    }

    public function loadCachedRoutes(): bool
    {
        $path = config('routing.cache_path');

        if (!is_file($path)) {
            return false;
        }

        $data = require $path;

        $this->routes = $data['routes'] ?? [];
        $this->processedRoutes = $data['processedRoutes'] ?? [];

        return true;
    }

    public function cacheRoutes(): void
    {
        foreach ($this->processedRoutes as $route) {
            if ($route->hasClosureCallback()) {
                return;
            }
        }

        $path = config('routing.cache_path');

        echo '<pre>Caching routes to: ' . $path . '</pre>';

        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $data = [
            'routes' => $this->routes,
            'processedRoutes' => $this->processedRoutes,
        ];

        file_put_contents(
            $path,
            '<?php return ' . var_export($data, true) . ';'
        );
    }

    public function clearRouteCache(): void
    {
        $path = config('routing.cache_path');

        if (is_file($path)) {
            unlink($path);
        }
    }

    public function routeRequest($rewrite = false)
    {
        $routeNotAllowed = false;

        try {

            if ($rewrite === false) {
                // $this->loadRoutes();
                $this->ensureRoutesLoaded();

                if ($this->csrfVerifier !== null) {

                    /* Verify csrf token for request */
                    $this->csrfVerifier->run($this->request, fn($request) => $request);
                }
            }

            $url = $this->request->getRewriteUrl() ?? $this->request->getUri();

            $max = count($this->processedRoutes) - 1;

            /* @var $route ILoadableRoute */
            for ($i = $max; $i >= 0; $i--) {

                $route = $this->processedRoutes[$i];

                /* If the route matches */
                if ($route->matchRoute($url, $this->request) === true) {

                    /* Check if request method matches */
                    if (count($route->getRequestMethods()) > 0 && in_array($this->request->getMethod(), $route->getRequestMethods(), false) === false) {
                        $routeNotAllowed = true;
                        continue;
                    }

                    $route->loadMiddleware($this->request);

                    $rewriteRoute = $this->request->getRewriteRoute();

                    if ($rewriteRoute !== null) {
                        $rewriteRoute->loadMiddleware($this->request);
                        $rewriteRoute->renderRoute($this->request);

                        return;
                    }

                    /* If the request has changed */
                    $rewriteUrl = $this->request->getRewriteUrl();

                    if ($rewriteUrl !== null && $rewriteUrl !== $url) {
                        // unset($this->processedRoutes[$i]);
                        // $this->processedRoutes = array_values($this->processedRoutes);
                        // $this->routeRequest(true);

                        // return;

                        return $this->routeRequest(true);
                    }

                    /* Render route */
                    $routeNotAllowed = false;
                    $this->request->setLoadedRoute($route);
                    return $route->renderRoute($this->request);
                }
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }

        if ($routeNotAllowed) {
            $this->handleException(new HttpException('Route or method not allowed', 403));
        }

        if ($this->request->getLoadedRoute() === null) {

            $rewriteUrl = $this->request->getRewriteUrl();

            $uri = $this->request->getUri();
            if ($rewriteUrl !== null) {
                $message = sprintf('Route not found: "%s" (rewrite from: "%s")', $rewriteUrl, $uri);
            } else {
                $message = sprintf('Route not found: "%s"', $uri);
            }

            if (env('MATCH_ROUTES_TO_CONTROLLERS', false) || env('IMPLICIT_ROUTING', false)) {
                return $this->matchRoutesToControllers($this->request);
            } else {
                $this->handleException(new NotFoundHttpException($message, 404));
            }
        }
    }

    protected function matchRoutesToControllers(Request $request)
    {
        $url = explode('/', filter_var(trim((str_contains($request->getHost(), ':')) ? $request->getUri(true) : $request->getUri(), '/'), FILTER_SANITIZE_URL));
        if ($url[0] != '' && $url[0] != '/') {
            $this->defaultRouteCollection['controller'] = ucfirst((string) Str::camelize(str_replace('-', '_', $url[0])));
            unset($url[0]);
        }

        if (isset($url[1])) {
            $this->defaultRouteCollection['method'] = Str::camelize(str_replace('-', '_', $url[1]));
            unset($url[1]);
        }

        $this->defaultRouteCollection['params'] = $url !== [] ? array_values($url) : [];
        $controller = '\\' . env('APP_NAMESPACE', 'App') . '\\Controllers\\' . str_ireplace('controller', '', explode("?", (string) $this->defaultRouteCollection['controller'])[0]) . 'Controller';

        $method = explode('?', (string) $this->defaultRouteCollection['method'])[0];
        $params = $this->defaultRouteCollection['params'];
        if (class_exists($controller)) {
            $controller = Application::getInstance()->resolve($controller);

            if (method_exists($controller, $method)) {
                $result = call_user_func_array([$controller, $method], $this->methodInjection($controller, $method, $params));
                if ($result instanceof ViewModel || is_string($result) || $result instanceof View) {
                    return $result;
                } elseif ($result instanceof Redirect) {
                    if ($result->getPath() !== null) {
                        $result->header('Location: ' . $result->getPath());
                        exit();
                    } else {
                        throw new NotFoundHttpException("You have not provided a Redirect URL");
                    }
                }
                return null;
            } else {
                $message = sprintf('Method: "%s" not found', $method);
                $this->handleException(new NotFoundHttpMethodException($message, 404));
            }
        } else {
            $message = sprintf('Controller: "%s" not found', $controller);
            $this->handleException(new NotFoundHttpControllerException($message, 404));
        }
        return null;
    }

    protected function handleException(\Throwable $e)
    {
        $url = $this->request->getRewriteUrl() ?? $this->request->getUri();

        $max = count($this->exceptionHandlers);

        /* @var $handler IExceptionHandler */
        for ($i = 0; $i < $max; $i++) {

            $handler = $this->exceptionHandlers[$i];

            if (is_object($handler) === false) {
                $handler = new $handler();
            }

            if (($handler instanceof IException) === false) {
                throw new HttpException('Exception  must implement the IException interface.', 500);
            }

            if ($handler->handleError($this->request, $e) !== null) {

                $rewriteRoute = $this->request->getRewriteRoute();

                if ($rewriteRoute !== null) {
                    $rewriteRoute->loadMiddleware($this->request);
                    $rewriteRoute->renderRoute($this->request);

                    return;
                }

                $rewriteUrl = $this->request->getRewriteUrl();

                /* If the request has changed */
                if ($rewriteUrl !== null && $rewriteUrl !== $url) {
                    unset($this->exceptionHandlers[$i]);
                    $this->exceptionHandlers = array_values($this->exceptionHandlers);
                    $this->routeRequest(true);

                    return;
                }
            }
        }

        throw $e;
    }

    public function arrayToParams(array $getParams = [], $includeEmpty = true)
    {
        if (count($getParams) > 0) {

            if ($includeEmpty === false) {
                $getParams = array_filter($getParams, fn($item) => trim((string) $item) !== '');
            }

            return '?' . http_build_query($getParams);
        }

        return '';
    }

    /**
     * Find route by alias, class, callback or method.
     *
     * @param string $name
     * @return ILoadableRoute|null
     */
    public function findRoute($name)
    {
        $max = count($this->processedRoutes) - 1;

        /* @var $route ILoadableRoute */
        for ($i = $max; $i >= 0; $i--) {

            $route = $this->processedRoutes[$i];

            /* Check if the name matches with a name on the route. Should match either router alias or controller alias. */
            if ($route->hasName($name)) {
                return $route;
            }

            /* Direct match to controller */
            if ($route instanceof IControllerRoute && strtolower($route->getController()) === strtolower($name)) {
                return $route;
            }

            /* Using @ is most definitely a controller@method or alias@method */
            if (is_string($name) && str_contains($name, '@')) {
                [$controller, $method] = array_map(strtolower(...), explode('@', $name));

                if ($controller === strtolower((string) $route->getClass()) && $method === strtolower((string) $route->getMethod())) {
                    return $route;
                }
            }

            /* Check if callback matches (if it's not a function) */
            if (is_string($name) && is_string($route->getCallback()) && str_contains($name, '@') && str_contains($route->getCallback(), '@') && is_callable($route->getCallback()) === false) {

                /* Check if the entire callback is matching */
                if (str_starts_with($route->getCallback(), $name) || strtolower($route->getCallback()) === strtolower($name)) {
                    return $route;
                }

                /* Check if the class part of the callback matches (class@method) */
                if (strtolower($name) === strtolower((string) $route->getClass())) {
                    return $route;
                }
            }
        }

        return null;
    }

    /**
     * Get url for a route by using either name/alias, class or method name.
     *
     * The name parameter supports the following values:
     * - Route name
     * - Controller/resource name (with or without method)
     * - Controller class name
     *
     * When searching for controller/resource by name, you can use this syntax "route.name@method".
     * You can also use the same syntax when searching for a specific controller-class "MyController@home".
     * If no arguments is specified, it will return the url for the current loaded route.
     *
     * @param string|null $name
     * @param string|array|null $parameters
     * @param array|null $getParams
     * @throws \InvalidArgumentException
     * @return string
     */
    public function getUrl($name = null, $parameters = null, $getParams = null)
    {
        if ($getParams !== null && is_array($getParams) === false) {
            throw new \InvalidArgumentException('Invalid type for getParams. Must be array or null');
        }

        /* Only merge $_GET when all parameters are null */
        $getParams = $name === null && $parameters === null && $getParams === null ? $_GET : (array)$getParams;

        /* Return current route if no options has been specified */
        if ($name === null && $parameters === null) {
            $url = rtrim(parse_url($this->request->getUri(), PHP_URL_PATH), '/');

            return (($url === '') ? '/' : $url . '/') . $this->arrayToParams($getParams);
        }

        $loadedRoute = $this->request->getLoadedRoute();

        /* If nothing is defined and a route is loaded we use that */
        if ($name === null && $loadedRoute !== null) {
            return $loadedRoute->findUrl($loadedRoute->getMethod(), $parameters, $name) . $this->arrayToParams($getParams);
        }

        /* We try to find a match on the given name */
        $route = $this->findRoute($name);

        if ($route !== null) {
            return $route->findUrl($route->getMethod(), $parameters, $name) . $this->arrayToParams($getParams);
        }

        /* Using @ is most definitely a controller@method or alias@method */
        if (is_string($name) && str_contains($name, '@')) {
            [$controller, $method] = explode('@', $name);

            /* Loop through all the routes to see if we can find a match */

            $max = count($this->processedRoutes) - 1;

            /* @var $route ILoadableRoute */
            for ($i = $max; $i >= 0; $i--) {

                $route = $this->processedRoutes[$i];

                /* Check if the route contains the name/alias */
                if ($route->hasName($controller)) {
                    return $route->findUrl($method, $parameters, $name) . $this->arrayToParams($getParams);
                }

                /* Check if the route controller is equal to the name */
                if ($route instanceof IControllerRoute && strtolower($route->getController()) === strtolower($controller)) {
                    return $route->findUrl($method, $parameters, $name) . $this->arrayToParams($getParams);
                }
            }
        }

        /* No result so we assume that someone is using a hardcoded url and join everything together. */
        $url = trim(implode('/', array_merge((array)$name, (array)$parameters)), '/');

        return (($url === '') ? '/' : '/' . $url . '/') . $this->arrayToParams($getParams);
    }

    /**
     * Get bootmanagers
     * @return array
     */
    public function getBootManagers()
    {
        return $this->bootManagers;
    }

    /**
     * Set bootmanagers
     */
    public function setBootManagers(array $bootManagers)
    {
        $this->bootManagers = $bootManagers;
    }

    /**
     * Add bootmanager
     */
    public function addBootManager(IRouterBootManager $bootManager)
    {
        $this->bootManagers[] = $bootManager;
    }

    /**
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }

    /**
     * Set routes
     *
     * @return static $this
     */
    public function setRoutes(array $routes)
    {
        $this->routes = $routes;

        return $this;
    }

    /**
     * Get current request
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get csrf verifier class
     * @return BaseCsrfVerifier
     */
    public function getCsrfVerifier()
    {
        return $this->csrfVerifier;
    }

    /**
     * Set csrf verifier class
     *
     * @return static
     */
    public function setCsrfVerifier(BaseCsrfVerifier $csrfVerifier)
    {
        $this->csrfVerifier = $csrfVerifier;

        return $this;
    }
}
