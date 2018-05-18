<?php
namespace Yuga\Http;

use InvalidArgumentException;

class Response
{
    protected $request;
    public $redirect;

    public function __construct(Request $request, Redirect $redirect)
    {
        $this->request = $request;
        $this->redirect = $redirect;
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
    public function redirect($url, $httpCode = null)
    {
        if ($httpCode !== null) {
            $this->httpCode($httpCode);
        }
        return $this->redirect->to($url);
    }

    public function refresh()
    {
        $this->redirect($this->request->getUri());
    }

    /**
     * Add http authorisation
     * @param string $name
     * @return static
     */
    public function auth($name = '')
    {
        $this->headers([
            'WWW-Authenticate: Basic realm="' . $name . '"',
            'HTTP/1.0 401 Unauthorized',
        ]);

        return $this;
    }

    public function cache($eTag, $lastModified = 2592000)
    {

        $this->headers([
            'Cache-Control: public',
            'Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
            'Etag: ' . $eTag,
        ]);

        $httpModified = $this->request->getHeader('http-if-modified-since');
        $httpIfNoneMatch = $this->request->getHeader('http-if-none-match');

        if (($httpIfNoneMatch !== null && $httpIfNoneMatch === $eTag) || ($httpModified !== null && strtotime($httpModified) === $lastModified)) {

            $this->header('HTTP/1.1 304 Not Modified');

            exit();
        }

        return $this;
    }

    /**
     * Json encode array
     * @param array $value
     */
    public function json($value, $options = null, $code = 200, $dept = 512)
    {
        if (($value instanceof \JsonSerializable) === false && \is_array($value) === false) {
            throw new InvalidArgumentException('Invalid type for parameter "value". Must be of type array or object implementing the \JsonSerializable interface.');
        }
        $this->httpCode($code);
        $this->header('Content-Type: application/json; charset=utf-8');
        echo json_encode($value, $options, $dept);
        exit(0);
    }

    /**
     * Add header to response
     * @param string $value
     * @return static
     */
    public function header($value)
    {
        header($value);

        return $this;
    }

    /**
     * Add multiple headers to response
     * @param array $headers
     * @return static
     */
    public function headers(array $headers)
    {
        foreach ($headers as $header) {
            $this->header($header);
        }

        return $this;
    }

    public function getOrSetVars()
    {
        return \App::make('view');
    }

}