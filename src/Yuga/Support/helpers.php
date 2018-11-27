<?php
/**
 * @author Mahad Tech Solutions
 */

if (! function_exists('view')) {
    function view($args = null, $data = null)
    {
        if ($args instanceof \Yuga\View\ViewModel)
            return viewModel($args);

        return new \Yuga\Views\View($args, $data);
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
            return app()->make('session')->exists($param);
        return app()->make('session');
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
                if ($target instanceof \Yuga\Collection) {
                    $target = $target->all();
                } elseif (! is_array($target)) {
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
        return host('/storage/'. $file);
    }
}



if(!function_exists('host')) {
    function host($value ="")
    {
        return scheme(response()->getOrSetVars()->host . '/' . ltrim($value, '/'));
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
        return isset($_ENV[$key]) ? $_ENV[$key] : $default;
    }
}

if (!function_exists('app')) {
    function app()
    {
        return \Yuga\Application\Application::getInstance();
    }
}
if(!function_exists('route')) {
    function route($name = null, $parameters = null, $getParams = null)
    {
        if (!is_null(request()->processHost())) {
            if (strpos(request()->getHost(), ':') !== false) {
                return rtrim(Route::getUrl($name, $parameters, $getParams), '/');
            } else {
                return rtrim(request()->processHost().Route::getUrl($name, $parameters, $getParams), '/');
            }
        }
        return Route::getUrl($name, $parameters, $getParams);
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
    function redirect($url, $code = null)
    {
        if ($code !== null) {
            response()->httpCode($code);
        }

        response()->redirect($url);
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
            if (strpos(request()->getHost(), ':') !== false) {
                return '/'.$value;
            } else {
                return scheme(request()->getHost().request()->processHost().'/'.$value);
            }
        }
        return scheme(request()->getHost().'/'.$value);
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
        return $_ENV['base_path'].'/'.$file;
    }
}

if (!function_exists('storage')) {
    function storage($path = null) 
    {
        return path('storage/' . $path);
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
    function config($key, $default = 'Yuga')
    {
        return app()->config->load('config.Settings')->get($key, $default);
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