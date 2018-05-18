<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Route\Router;
use Yuga\Http\Request;

class RouteUrl extends LoadableRoute
{
    public function __construct($url, $callback)
    {
        $this->setUrl($url);
        $this->setCallback($callback);
    }

    public function matchRoute($url, Request $request)
    {

        $url = parse_url(urldecode($url), PHP_URL_PATH);
        $url = rtrim($url, '/') . '/';

        if (!is_null(env('APP_FOLDER'))) {
           $url = str_replace('/'.env('APP_FOLDER'), '', $url); 
        }
        
        /* Match global regular-expression for route */
        $regexMatch = $this->matchRegex($request, $url);

        if ($regexMatch === false) {
            return false;
        }

        /* Parse parameters from current route */
        $parameters = $this->parseParameters($this->url, $url);

        /* If no custom regular expression or parameters was found on this route, we stop */
        if ($regexMatch === null && $parameters === null) {
            return false;
        }

        /* Set the parameters */
        $this->setParameters((array)$parameters);

        return true;
    }

}