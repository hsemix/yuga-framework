<?php

namespace Yuga\Http;

use Exception;
use Yuga\Route\Route;
use Yuga\Http\Input\Input;
use Yuga\Validate\Validate;
use Yuga\Route\Router\RouteUrl;
use Yuga\Application\Application;
use Yuga\Route\Support\ILoadableRoute;

class Request
{
    private $data = [];
    protected $headers;
    protected $host;
    protected $uri;
    protected $method;
    protected $input;
    protected $app;
    /**
     * @var ILoadableRoute|null
     */
    protected $rewriteRoute;
    protected $rewriteUrl;

    /**
     * @var ILoadableRoute|null
     */
    protected $loadedRoute;

    /**
     * Form fields
     */
    protected $formFields = [];

    protected $model;

    public function __construct()
    {
        $this->parseHeaders();
        $this->host = $this->getHeader('http-host');
        $this->uri = $this->getHeader('request-uri');
        $this->input = new Input($this);
        $this->method = strtolower($this->input->get('_method', $this->getHeader('request-method'), 'post'));
        // $this->app = Application::getInstance();
        $this->data = $this->except(['_token']);

        event('on:request:start', ['request' => $this]);
    }

    protected function parseHeaders()
    {
        $this->headers = [];

        $max = count($_SERVER) - 1;
        $keys = array_keys($_SERVER);

        for ($i = $max; $i >= 0; $i--) {
            $key = $keys[$i];
            $value = $_SERVER[$key];

            $this->headers[strtolower($key)] = $value;
            $this->headers[strtolower(str_replace('_', '-', $key))] = $value;
        }

    }

    public function create()
    {
        return new static;
    }

    public function isSecure()
    {
        return $this->getHeader('http-x-forwarded-proto') === 'https' || $this->getHeader('https') !== null || $this->getHeader('server-port') === 443;
    }

    public function getPort()
    {
        return $this->getHeader('server-port');
    }

    /**
     * @return string
     */
    public function getUri($trim = false)
    {
        if ($trim) {
            return $this->uri;
        }
        return '/' . ltrim(str_replace($this->processHost(), '', $this->uri), '/');
    }


    public function processHost()
    {
        $scriptName = $this->getHeader('php-self');

        if (env('PAGE_CONTROLLER', 'index.php') == 'index.php') {
            $scriptName = str_replace('/index.php', '', $this->getHeader('php-self'));
        }
        
        $segs = explode('/', trim($scriptName, '/'));
        $segs = array_reverse($segs);
        $index = 0;
        $last = count($segs);
        $baseUrl = '';

        do {
            $seg = $segs[$index];
            $baseUrl = '/' . $seg . rtrim($baseUrl, '/') ;
            ++$index;
        } while ($last > $index && (false !== $pos = strpos($scriptName, $baseUrl)) && 0 != $pos);

        return $baseUrl . '/';
    }

    public function formatUrl($url)
    {
        echo $this->uri;
        die();
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    public function getServer()
    {
        return $this->getHeader('server-name');
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get http basic auth user
     * @return string|null
     */
    public function getUser()
    {
        return $this->getHeader('php-auth-user');
    }

    /**
     * Get http basic auth password
     * @return string|null
     */
    public function getPassword()
    {
        return $this->getHeader('php-auth-pw');
    }

    /**
     * Get all headers
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get id address
     * @return string
     */
    public function getIp()
    {
        if ($this->getHeader('http-cf-connecting-ip') !== null) {
            return $this->getHeader('http-cf-connecting-ip');
        }

        if ($this->getHeader('http-x-forwarded-for') !== null) {
            return $this->getHeader('http-x-forwarded-for');
        }

        return $this->getHeader('remote-addr');
    }

    /**
     * Get remote address/ip
     *
     * @alias static::getIp
     * @return string
     */
    public function getRemoteAddr()
    {
        return $this->getIp();
    }

    /**
     * Get referer
     * @return string
     */
    public function getReferer()
    {
        return $this->getHeader('http-referer');
    }

    /**
     * Get user agent
     * @return string
     */
    public function getUserAgent()
    {
        return $this->getHeader('http-user-agent');
    }

    /**
     * Get header value by name
     *
     * @param string $name
     * @param string|null $defaultValue
     *
     * @return string|null
     */
    public function getHeader($name, $defaultValue = null)
    {
        if (isset($this->headers[strtolower($name)])) {
            return $this->headers[strtolower($name)];
        }

        $max = count($_SERVER) - 1;
        $keys = array_keys($_SERVER);

        for ($i = $max; $i >= 0; $i--) {

            $key = $keys[$i];
            $name = $_SERVER[$key];

            if ($key === $name) {
                return $name;
            }
        }

        return $defaultValue;
    }

    public function getBearerToken()
    {
        if ($authorize = $this->getHeader('http-authorization')) {
            if (preg_match('/Bearer\s(\S+)/', $authorize, $matches)) {
                return $matches[1];
            }
        }

        throw new Exception('Access Token Not Found', 401);
    }

    /**
     * Get input class
     * @return Input
     */
    public function getInput()
    {
        return $this->input;
    }

    public function get($value, $defaultValue = null)
    {
        return $this->getInput()->get($value, $defaultValue);
    }

    /**
     * Is format accepted
     *
     * @param string $format
     *
     * @return bool
     */
    public function isFormatAccepted($format)
    {
        return ($this->getHeader('http-accept') !== null && stripos($this->getHeader('http-accept'), $format) > -1);
    }

    /**
     * Get accept formats
     * @return array
     */
    public function getAcceptFormats()
    {
        return explode(',', $this->getHeader('http-accept'));
    }

    /**
     * @param string $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @param string $host
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * @param string $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * Set rewrite route
     *
     * @param ILoadableRoute $route
     * @return static
     */
    public function setRewriteRoute(ILoadableRoute $route)
    {
        $this->rewriteRoute = $route;

        $callback = $route->getCallback();

        /* Only add default namespace on relative callbacks */
        if ($callback === null || $callback[0] !== '\\') {

            $namespace = Route::getDefaultNamespace();

            if ($namespace !== null) {

                if ($this->rewriteRoute->getNamespace() !== null) {
                    $namespace .= '\\' . $this->rewriteRoute->getNamespace();
                }

                $this->rewriteRoute->setDefaultNamespace($namespace);

            }

        }

        return $this;
    }

    /**
     * Get rewrite route
     *
     * @return ILoadableRoute|null
     */
    public function getRewriteRoute()
    {
        return $this->rewriteRoute;
    }

    /**
     * Get rewrite url
     *
     * @return string
     */
    public function getRewriteUrl()
    {
        return $this->rewriteUrl;
    }

    /**
     * Set rewrite url
     *
     * @param string $rewriteUrl
     * @return static
     */
    public function setRewriteUrl($rewriteUrl)
    {
        $this->rewriteUrl = $rewriteUrl;

        return $this;
    }

    /**
     * Set rewrite callback
     * @param string $callback
     * @return static
     */
    public function setRewriteCallback($callback)
    {
        return $this->setRewriteRoute(new RouteUrl($this->uri, $callback));
    }

    /**
     * Get loaded route
     * @return ILoadableRoute|null
     */
    public function getLoadedRoute()
    {
        return $this->loadedRoute;
    }

    /**
     * Set loaded route
     *
     * @param ILoadableRoute $route
     * @return static
     */
    public function setLoadedRoute(ILoadableRoute $route)
    {
        $this->loadedRoute = $route;

        return $this;
    }

    public function __isset($name)
    {
        return array_key_exists($name, $this->data);
    }

    public function __set($name, $value = null)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    public function exists($index = null)
    {
		return $this->getInput()->exists($index);
    }

    public function files($key = null, $default = null)
    {
        $files = $this->getInput()->findFile($key, $default);
		if (is_array($files)) 
			return $this->getInput()->findFile($key, $default);
		else if(!$this->getInput()->findFile($key, $default)->hasError())
			return $this->getInput()->findFile($key, $default);
        return false;
    }

    public function hasFile($key)
    {
        return $this->getInput()->hasFile($key);
    }
    public function all(array $filter = null)
    {
        return $this->getInput()->all($filter);
    }

    public function only(array $onlyFields = [])
    {
        $only = [];
        foreach ($this->all() as $field => $value) {
            if (in_array($field, $onlyFields))
                $only[$field] = $value;
        }
       return $only;
    }

    public function except(array $exceptFields = [])
    {
        $only = [];
        foreach ($this->all() as $field => $value) {
            if (!in_array($field, $exceptFields))
                $only[$field] = $value;
        }
       return $only;
    }
    
    public function isAjax()
    {
        if (($this->getHeader('http-x-requested-with') !== null && strtolower($this->getHeader('http-x-requested-with')) === 'xmlhttprequest') || ($this->getHeader('http-user-agent') !== null && preg_match('/^(curl|wget)/i', $this->getHeader('http-user-agent')))) {
            return true;
        }
        return false;
    }

    public function addOld()
    {
        app()->make('session')->put('old-data', $this->getInput()->all());
        return $this;
    }
    public function old($key = null)
    {
        $data = app()->make('session')->get('old-data');
        if ($key && !is_null($data)) {
            return isset($data[$key]) ? $data[$key]: null;
        }
        return $data;
    }

    public function user()
    {
        return \Auth::user();
    }

    public function guest()
    {
        return \Auth::guest();
    }

    public function validate($rules = [], $clearOldData = true)
    {
        $fields = $this->all();
        if (isset($fields['_token']))
            unset($fields['_token']);
        
        $validation = app()->get('validate')->check($this->all(), $rules);
        if ($validation->failed()) {
            if ($this->isAjax()) {
                return $validation->errors();
            } else {
                app()->get('validate')->getSession()->put('errors', $validation->errors());
                $this->addOld();
                return app()->get('validate')->getResponse()->redirect->back();
            } 
        }
        if ($clearOldData) {
            app()->get('validate')->getSession()->delete('old-data');
        }
        if ($this->isAjax() || $this->getHeader('http-accept') == 'application/json' || $this->getHeader('http-content-type') == 'text/plain') {
            return $validation;
        }
        return $validation->getValidated();
    }

    public function getRouteParams($key = null)
    {   
        return $this->getLoadedRoute()->getParams($key);
    }

    public function model()
    {
        return $this->model;
    }

    public function setModel($model)
    {
        $this->model = $model;
        return $this;
    }

}