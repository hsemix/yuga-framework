<?php

namespace Yuga\Http\Middleware;

class MiddleWare
{
    /**
     * The apps route's middleware is to be registered here
     *
     * @var array
     */
    public $routerMiddleWare = [
		'auth' => \Yuga\Authenticate\Authenticate::class,
		'remembered' => \Yuga\Authenticate\LoginWithRemember::class,
	];

    /**
     * The routes exceptions
     */
    public $except = [
        
    ];

    public function except(array|string $except = null)
    {
        if (is_array($except)) {
            $this->except = $except;
        } else {
            $this->except[] = $except;
        }
        
        return $this;
    }

    public function run($request, $next)
    {
        return $next($request);
    }
}
