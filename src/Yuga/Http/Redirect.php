<?php

namespace Yuga\Http;

use Yuga\Route\Route;
use Yuga\Application\Application;

class Redirect
{
    protected ?string $path = null;
    protected int $statusCode = 302;
    protected array $headers = [];

    public function __construct(protected ?Request $request = null)
    {
    }

    public function setPath(?string $path = null)
    {
        $this->path = $path;
        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function httpCode($code)
    {
        $this->statusCode = (int) $code;
        return $this;
    }

    public function header($value)
    {
        if (str_contains((string) $value, ':')) {
            [$name, $headerValue] = explode(':', (string) $value, 2);
            $this->headers[trim($name)] = trim($headerValue);
        }

        return $this;
    }

    public function to($url, $httpCode = null)
    {
        if ($httpCode !== null) {
            $this->httpCode($httpCode);
        }

        if ($this->isValidUri($url)) {
            $url = $this->cleanUrl($url);
        } else {
            $resolved = route($url);
            $url = $resolved === '' ? $url : $resolved;
        }

        $this->setPath($url);
        $this->headers['Location'] = $url;

        return $this;
    }

    public function route($name = null, $parameters = null, $getParams = null)
    {
        $url = Route::getUrl($name, $parameters, $getParams);

        if (!is_null(request()->processHost()) && !str_contains((string) request()->getHost(), ':')) {
            $url = rtrim(request()->processHost() . $url, '/');
        }

        return $this->to($url);
    }

    public function back()
    {
        return $this->to($this->request->getReferer() ?: '/', 302);
    }

    public function refresh()
    {
        $this->headers['Refresh'] = '0';
        return $this;
    }

    public function httpUrl($url)
    {
        return $this->to($url, 301);
    }

    protected function isValidUri($uri)
    {
        return is_string($uri) && str_starts_with($uri, 'http');
    }

    protected function cleanUrl($uri)
    {
        $firstSection = explode('://', (string) $uri, 2);

        if (count($firstSection) < 2) {
            return $uri;
        }

        $http = $firstSection[0];
        $path = explode('/', $firstSection[1]);

        $url = array_map(fn($element) => str_contains($element, ':') ? $element : rawurlencode($element), $path);

        return $http . '://' . implode('/', $url);
    }

    public function with($key = null, $value = null)
    {
        if ($key) {
            if (is_array($value)) {
                foreach ($value as $var => $item) {
                    $this->with($var, $item);
                }
            } else {
                Application::getInstance()->get('session')->flash($key, $value);
            }
        }

        return $this;
    }

    public function __call($method, $parameters)
    {
        if (preg_match('/^with(.+)$/', (string) $method, $matches)) {
            $decamelized = \Str::deCamelize($matches[1]);
            $camelized = \Str::camelize($decamelized);

            return $this->with($camelized, $parameters[0] ?? null);
        }

        return call_user_func_array([$this, $method], $parameters);
    }
}