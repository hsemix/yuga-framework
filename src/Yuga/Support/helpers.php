<?php

use Yuga\Async\Async;
use Yuga\Route\Route;
use Yuga\Support\Arr;
use Yuga\Support\Str;
use Yuga\Database\Elegant\Collection;
use Yuga\Interfaces\Queue\JobDispatcherInterface;
/**
 * @author Mahad Tech Solutions
 */

if (! function_exists('view')) {
    function view($viewName = null, array $data = null)
    {
        if ($viewName) {
            if ($viewName instanceof \Yuga\View\ViewModel)
                return viewModel($viewName);

            return new \Yuga\Views\View($viewName, $data);
        } else {
            // Magically find the view
            $trace = debug_backtrace(); 
            
            $inspector = $trace[1]; 
            $method = $inspector['function'];
            $methodLower = strtolower($method);
            $classPath = explode('\\', $inspector['class']);
            $class = str_replace('Controller', '', $classPath[count($classPath) - 1]);
            $classLower = strtolower($class);

            if (\file_exists(resources('views/' . $class . '/' . $method . '.php')) || \file_exists(resources('views/' . $class . '/' . $method . 'hax.php'))) {
                return new \Yuga\Views\View($class . '/' . $method, $data);
            } else {
                return new \Yuga\Views\View($classLower . '/' . $methodLower, $data);
            }
        }
    }
}

if (! function_exists('viewModel')) {
    function viewModel(\Yuga\View\ViewModel $viewModel)
    {
        echo($viewModel);
    }
}

if (! function_exists('session')) {
    function session($param = null)
    {
        if ($param)
            return app()->make('session')->get($param);
        return app()->make('session');
    }
}

if (! function_exists('db')) {
    function db($param = null)
    {
        if ($param)
            return app('db')->table($param);
        return app('db');
    }
}

if (! function_exists('cookie')) {
    function cookie()
    {
        return app()->make('cookie');
    }
}


if (! function_exists('csrf_token')) {
    function csrf_token()
    {
        $baseVerifier = Route::router()->getCsrfVerifier();
        if ($baseVerifier !== null) {
            return $baseVerifier->getToken();
        }
        return null;
    }
}

if (! function_exists('class_base')) {
    function class_base($class)
    {
        $class = is_object($class) ? get_class($class) : $class;
        return basename(str_replace('\\', '/', $class));
    }
}


if (! function_exists('data_get')) {
    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param  mixed   $target
     * @param  string|array  $key
     * @param  mixed   $default
     * @return mixed
     */
    function data_get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        while (($segment = array_shift($key)) !== null) {
            if ($segment === '*') {
                if ($target instanceof Collection) {
                    $target = $target->all();
                } elseif (!is_array($target)) {
                    return value($default);
                }

                $result = \Yuga\Support\Arr::pluck($target, $key);

                return in_array('*', $key) ? \Yuga\Support\Arr::collapse($result) : $result;
            }

            if (\Yuga\Support\Arr::accessible($target) && \Yuga\Support\Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }

        return $target;
    }
}

if (! function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if(!function_exists('resource')) {
    function resource($value ="")
    {
        return '/'.response()->getOrSetVars()->resource.$value;
    }
}

if (!function_exists('resources')) {
    function resources($file = '')
    {
        return path('resources/'. $file);
    }
}



if(!function_exists('host')) {
    function host($value = "", $includeHost = true)
    {
        $host = '';
        if ($includeHost) {
            if (!is_null(request()->processHost())) {
                if (request()->getServer() != request()->gethost()) {
                    $host = '/'.ltrim($value, '/');
                } else if(strpos(request()->processHost(), '/public') !== false) {
                    $host = scheme(request()->getHost() . '/' . ltrim(request()->processHost(), '/') . ltrim($value, '/'));
                } else {
                    $host = scheme(request()->getServer(). '/' . ltrim($value, '/'));
                } 
            } else {
                $host = scheme(request()->getHost() . '/' . ltrim($value, '/'));
            }
        } else {
            $host = '/'.ltrim($value, '/');
        }
        
        return $host;
    }
}

if(!function_exists('resource')) {
    function resource($value ="")
    {
        return '/'.response()->getOrSetVars()->resource.$value;
    }
}

if(!function_exists('env')) {
    function env($key, $default = null)
    {
        if (isset($_ENV[$key])) {
            return $_ENV[$key] === '' || is_null($_ENV[$key]) ? $default : $_ENV[$key];
        }
        return $default;
    }
}

if (!function_exists('app')) {
    function app($param = null)
    {
        if ($param) {
            if (class_exists($param))
                return \Yuga\Application\Application::getInstance()->resolve($param);
            else
                return \Yuga\Application\Application::getInstance()->make($param);
        }            
        return \Yuga\Application\Application::getInstance();
    }
}
if(!function_exists('route')) {
    function route($name = null, $parameters = null, $getParams = null)
    {
        $route = Route::getUrl($name, $parameters, $getParams);
        if (!is_null(request()->processHost())) {
            if (strpos(request()->getHost(), ':') !== false) {
                $route = rtrim(Route::getUrl($name, $parameters, $getParams), '/');
                if (str_contains(request()->getUri(true), 'public')) {
                    $route = rtrim(request()->processHost().ltrim(Route::getUrl($name, $parameters, $getParams), '/'), '/');
                }
            } else {
                if(strpos(request()->processHost(), '/public') !== false) {
                    $route = rtrim(request()->processHost().ltrim(Route::getUrl($name, $parameters, $getParams), '/'), '/');
                } else {
                    $route = rtrim(Route::getUrl($name, $parameters, $getParams), '/');
                }
            }
        }
        return $route;
    }
}

/**
* @return \Yuga\Http\Response
*/
if(!function_exists('response')) {
    function response()
    {
        return Route::response();
    }
}

/**
* @return \Yuga\Http\Request
*/
if(!function_exists('request')) {
    function request()
    {
        return Route::request();
    }
}

/**
* Get input class
* @return \Yuga\Http\Input\Input
*/
if(!function_exists('input')) {
    function input()
    {
        return request()->getInput();
    }
}

if(!function_exists('redirect')) {
    function redirect($url = null, $code = null)
    {
        if ($code !== null) {
            response()->httpCode($code);
        }
        
        return response()->redirect($url);
    }
}

if(!function_exists('full_host')) {
    function full_host($value ="")
    {
        return host($value);
    }
}

if(!function_exists('scheme')) {
    function scheme($value = null)
    {
        $scheme = isset($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : 'http';
        return $scheme .'://'.$value;
    }
}

if(!function_exists('assets')) {
    function assets($value = "")
    {
        if (!is_null(request()->processHost())) {
            if (request()->getServer() != request()->gethost()) {
                if (str_contains(request()->getUri(true), 'public')) {
                    return scheme(request()->getHost() . '/' . ltrim(request()->processHost(), '/') . ltrim($value, '/'));
                }
                return '/'.ltrim($value, '/');
            } else if(strpos(request()->processHost(), '/public') !== false) {
                return scheme(request()->getHost() . '/' . ltrim(request()->processHost(), '/') . ltrim($value, '/'));
            } else {
                return scheme(request()->getServer(). '/' . ltrim($value, '/'));
            } 
        } else {
            return scheme(request()->getHost() . '/' . ltrim($value, '/'));
        }
    }
}

if(!function_exists('asset')) {
    function asset($value = "")
    {
        return scheme(request()->getHost().'/'.$value);
    }
}

if(!function_exists('slug')) {
    function slug($key, $separator = '-')
    {
        return \Yuga\Support\Str::slug($key, $separator);
    }
}

if(!function_exists('array_get')) {
    function array_get($array, $key, $default = null)
    {
        if (is_null($key)) return $array;
        foreach (explode('.', $key) as $segment)
        {
            if ( ! is_array($array) or ! array_key_exists($segment, $array))
            {
                return value($default);
            }

            $array = $array[$segment];
        }

        return $array;
    }
}

if (!function_exists('path')) {
    function path($file = null)
    {
        return $_ENV['base_path'] . DIRECTORY_SEPARATOR . $file;
    }
}

if (!function_exists('storage')) {
    function storage($path = null) 
    {
        return path('storage' . DIRECTORY_SEPARATOR . $path);
    }
}


if (!function_exists('debug')) {
    function debug($text)
    {
        if (app()->getDebugEnabled() === true) {
            app()->debug->add($text);
        }
    }
}



if (!function_exists('css')) {
    function css($styles)
    {
        $css = '';
        if (is_array($styles)) {
            foreach ($styles as $style) {
                $css .= "<link href=\"".assets($style)."\" rel=\"stylesheet\">\n";
            }
        } else {
            $css .= "<link href=\"".assets($styles)."\" rel=\"stylesheet\">\n";
        }
        
        return $css;
    }
}

if (!function_exists('script')) {
    function script($scripts)
    {
        $js = '';
        if (is_array($scripts)) {
            foreach ($scripts as $script) {
                $js .= "<script type=\"text/javascript\" src=\"".assets($script)."\"></script>\n";
            }
        } else {
            $js .= "<script type=\"text/javascript\" src=\"".assets($scripts)."\"></script>\n";
        }

        return $js;
    }
}

if (!function_exists('token')) {
    function token()
    {
        return '<input type="hidden" name="_token" value="'. csrf_token() .'">';
    }
}

if (!function_exists('config')) {
    function config($key = null, $default = 'Yuga')
    {
        if ($key) {
            $fileKeys = explode('.', $key);
            $file = array_shift($fileKeys);
            if (file_exists(path('config/' . $file . '.php'))) {
                return app()->config->load('config.' . $file)->get(implode('.', $fileKeys), $default);
            }
            return app()->config->load('config.Settings')->get($key, $default);
        }
        return app()->config;
    }
}

if (!function_exists('old')) {
    function old($key = null)
    {
        return request()->old($key);
    }
}

/**
 * jQuery
 *
 * alias for Jquery::jQuery
 *
 * @access  public
 * @param   string   $selector
 * @return  Element
 */
function jq($selector) 
{
    return Yuga\View\Client\Jquery::addQuery($selector);
}

if ( ! function_exists('get_mimes')) {
	/**
	 * Returns the MIME types array from config/mimes.php
	 *
	 * @return	array
	 */
	function &get_mimes()
	{
		static $mimes;

		if (empty($mimes)) {
			$mimes = file_exists('mimes.php') ? require 'mimes.php' : [];
		}

		return $mimes;
	}
}


if ( ! function_exists('event')) {
	/**
	 * Returns the event object
	 *
	 * @return	array
	 */
	function event($eventName = "yuga.auto.events", $params = [])
	{
		return app()->get('events')->trigger($eventName, $params);
	}
}

if ( ! function_exists('jsonResponse')) {
	/**
	 * Returns json response
	 *
	 * @return	array
	 */
	function jsonResponse(array $data = [])
	{
		return response()->jsonResponse($data);
	}
}

if (!function_exists('e')) {
    /**
     * Escape HTML entities in a string.
     *
     * @param  string  $value
     * @return string
     */
    function e($value)
    {
        return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
    }
}

if (! function_exists('starts_with')) {
    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    function starts_with($haystack, $needles)
    {
        return Str::startsWith($haystack, $needles);
    }
}

if (!function_exists('str_contains')) {
    /**
     * Determine if a given string contains a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    function str_contains($haystack, $needles)
    {
        return Str::contains($haystack, $needles);
    }
}

if (!function_exists('array_pull')) {
    /**
     * Get a value from the array, and remove it.
     *
     * @param  array   $array
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    function array_pull(&$array, $key, $default = null)
    {
        return Arr::pull($array, $key, $default);
    }
}

if (!function_exists('array_fetch')) {
    /**
     * Fetch a flattened array of a nested array element.
     *
     * @param  array   $array
     * @param  string  $key
     * @return array
     */
    function array_fetch($array, $key)
    {
        return Arr::fetch($array, $key);
    }
}


if (!function_exists('last')) {
    /**
     * Get the last element from an array.
     *
     * @param  array  $array
     * @return mixed
     */
    function last($array)
    {
        return end($array);
    }
}

if (!function_exists('with')) {
    /**
     * Return the given object. Useful for chaining.
     *
     * @param  mixed  $object
     * @return mixed
     */
    function with($object)
    {
        return $object;
    }
}

if (!function_exists('dispatch'))
{
    /**
     * Dispatch a job to its appropriate handler.
     *
     * @param  mixed  $job
     * @return mixed
     */
    function dispatch($job)
    {
        return app(JobDispatcherInterface::class)->dispatch($job);
    }
}

if (!function_exists('windows_os')) {
    /**
     * Determine whether the current envrionment is Windows based.
     *
     * @return bool
     */
    function windows_os()
    {
        return strtolower(substr(PHP_OS, 0, 3)) === 'win';
    }
}


if (!function_exists('async')) {
    /**
     * Run an asynchronous operation
     *
     * @return mixed
     */
    function async($callable)
    {   
        if (is_callable($callable)) {
            $main = new \Fiber($callable);
            $main->start();
            // return $main->start();
            return $main;
        } else {
            throw new Exception("'$callable' is not a callable");
        }
        
    }
}

if (!function_exists('suspend')) {
    /**
     * Run an asynchronous operation
     *
     * @return mixed
     */
    function suspend()
    {   
        return \Fiber::suspend();
    }
}

if (!function_exists('awaitable')) {
    /**
     * Alias for suspend()
     *
     * @return mixed
     */
    function awaitable()
    {   
        return suspend();
    }
}

if (!function_exists('await')) {
    /**
     * awaitable operation
     *
     * @return mixed
     */
    function await($operation)
    {   
        if (!$operation instanceof \Fiber)
            $operation = async(fn() => $operation);

        return Async::await($operation);     
    }
}

if (!function_exists('trans')) {
    /**
     * Translates a string into a language
     */
    function translate($text, $language = 'en') 
    {
        // Attempt to load the exact language file first
        $lang_file = __DIR__ . "/lang/{$language}.php";
        
        // If the exact language file does not exist, try the base language (e.g., en for en-US)
        if (!file_exists($lang_file)) {
            $base_language = substr($language, 0, 2);
            $lang_file = __DIR__ . "/lang/{$base_language}.php";
        }
        
        // Load the translation file if it exists
        if (file_exists($lang_file)) {
            $translations = include($lang_file);
            return $translations[$text] ?? $text;
        }
        
        // Return the original text if no translation is found
        return $text;
    }
}