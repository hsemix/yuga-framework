<?php

namespace Yuga\Http;

use Yuga\Route\Route;
use Yuga\Application\Application;

class Redirect
{
    protected $request;

    protected $path;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function setPath(string $path = null)
    {
        $this->path = $path;
        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set the http status code
     *
     * @param int $code
     * @return static
     */
    public function httpCode($code)
    {
        http_response_code($code);

        return $this;
    }

    /**
     * Redirect the response
     *
     * @param string $url
     * @param int $httpCode
     */
    public function to($url, $httpCode = null)
    {
        if ($httpCode !== null) {
            $this->httpCode($httpCode);
        }
        if ($this->isValidUri($url)) {
            return $this->httpUrl($url);
        }

        $url = (route($url) == '') ? $url : route($url);
        $this->setPath($url);

        $this->header('Location: ' . $url);
        exit();
        return $this;
    }

    public function header($value)
    {
        header($value);

        return $this;
    }

    public function refresh()
    {
        return header("Refresh:0");
    }

    public function back()
    {
        $this->header("HTTP/1.1 301 Moved Permanently");
        $this->to($this->request->getReferer());
        exit();
    }

    public function route($name = null, $parameters = null, $getParams = null)
    {
        if (!is_null(request()->processHost())) {
            if (strpos(request()->getHost(), ':') !== false) {
                $route = Route::getUrl($name, $parameters, $getParams);
            } else {
                $route = rtrim(request()->processHost().Route::getUrl($name, $parameters, $getParams), '/');
            }
        }
        $this->header('Location: ' . $route);
        exit();
    }

    protected function isValidUri($uri)
    {
        return substr($uri, 0, 4) == 'http';
    }

    protected function cleanUrl($uri)
    {
        $firstSection = explode("://", $uri);
        $http = $firstSection[0];
        $secondSection = explode('/', $firstSection[1]);

        $url = array_map(function ($element) {
            return (strpos($element, ':') !== false) ? $element : urlencode($element);
        }, $secondSection);
        return $http."://".implode("/", $url);
    }

    public function httpUrl($url)
    {
        header("HTTP/1.1 301 Moved Permanently");
        $url = $this->cleanUrl($url);
        header("Location: {$url}");
        exit;
    }

    public function with($key = null, $value = null)
    {
        if ($key) {
            if (is_array($value)) {
                foreach ($value as $var => $value) {
                    $this->with($var, $value);	
                }
            } else {
                Application::getInstance()->get('session')->flash($key, $value);
            }
        }

        return $this;
    }

    public function __call($method, $parameters)
	{
        if (preg_match('/^with(.+)$/', $method, $matches)) {
			$decamelized = \Str::deCamelize($matches[1]);
			$camelized = \Str::camelize($decamelized);
			return $this->with($camelized, $parameters[0]);
        }
		return call_user_func_array([$this, $method], $parameters);
	}
}