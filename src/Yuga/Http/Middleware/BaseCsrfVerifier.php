<?php
namespace Yuga\Http\Middleware;

use Yuga\CsrfToken;
use Yuga\Http\Request;
use Yuga\Http\Middleware\Exceptions\TokenMismatchException;

class BaseCsrfVerifier implements IMiddleware
{
    const POST_KEY = '_token';
    const HEADER_KEY = 'X-CSRF-TOKEN';

    protected $except;
    protected $csrfToken;
    protected $token;

    public function __construct()
    {
        $this->csrfToken = new CsrfToken();

        // Generate or get the CSRF-Token from Cookie.
        $this->token = ($this->hasToken() === false) ? $this->generateToken() : $this->csrfToken->getToken();
    }

    /**
     * Check if the url matches the urls in the except property
     * @param Request $request
     * @return bool
     */
    protected function skip(Request $request)
    {
        if ($this->except === null || is_array($this->except) === false) {
            return false;
        }

        $max = count($this->except) - 1;

        for ($i = $max; $i >= 0; $i--) {
            $url = $this->except[$i];

            $url = rtrim($url, '/');
            if ($url[strlen($url) - 1] === '*') {
                $url = rtrim($url, '*');
                $skip = (stripos($request->getUri(), $url) === 0);
            } else {
                $skip = ($url === rtrim($request->getUri(), '/'));
            }

            if ($skip === true) {
                return true;
            }
        }

        return false;
    }

    public function run(Request $request, \Closure $next)
    {

        if ($this->skip($request) === false && in_array($request->getMethod(), ['post', 'put', 'delete'], false) === true) {

            $token = $request->getInput()->get(static::POST_KEY, null, 'post');

            // If the token is not posted, check headers for valid x-csrf-token
            if ($token === null) {
                $token = $request->getHeader('http-' . static::HEADER_KEY);
            }

            if ($token == null) {
                throw new TokenMismatchException('Invalid form, Add csrf-token.');
            }
            if ($this->csrfToken->validate($token) === false) {

                if (app()->getDebugEnabled() === true) {
                    throw new TokenMismatchException('Invalid csrf-token.');
                } else {
                    if ($request->isAjax()) {
                        throw new TokenMismatchException('Your form has expired, please refresh the page and try again.');
                    } else {
                        $request->setRewriteCallback('Yuga\Controllers\PageController@formExpired');
                        return $request;
                    }
                }
                
            }
            return $next($request);
        }

    }

    public function generateToken()
    {
        $token = CsrfToken::generateToken();
        $this->csrfToken->setToken($token);

        return $token;
    }

    public function hasToken()
    {
        if ($this->token !== null) {
            return true;
        }

        return $this->csrfToken->hasToken();
    }

    public function getToken()
    {
        return $this->token;
    }

}