<?php
namespace Yuga\Http;

use Yuga\Route\Route;

class Redirect
{
    protected $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
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
        if (!is_null(env('APP_FOLDER'))) {
            if ($url == '/') {
                $url = '/'.env('APP_FOLDER').$url;
            } else {
                $url = ltrim($url, '/');
                if (strpos($url, env('APP_FOLDER')) !== false && !$this->isValidUri($url)) {
                    $url = str_replace(env('APP_FOLDER').'/', '', $url);
                }
            }
        }

        if ($httpCode !== null) {
            $this->httpCode($httpCode);
        }
        if ($this->isValidUri($url)) {
            return $this->httpUrl($url);
        }

        $this->header('location: ' . $url);
        die();
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
        $route = Route::getUrl($name, $parameters, $getParams);
        $this->to($route);
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
}