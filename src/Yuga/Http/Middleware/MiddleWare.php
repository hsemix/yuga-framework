<?php

namespace Yuga\Http\Middleware;

class MiddleWare
{
    /**
     * The apps route's middleware is to be registered here.
     *
     * @var array
     */
    public $routerMiddleWare = [
        'auth'       => \Yuga\Authenticate\Authenticate::class,
        'remembered' => \Yuga\Authenticate\LoginWithRemember::class,
    ];

    /**
     * The routes exceptions.
     */
    public $except = [

    ];

    public function except($except = null)
    {
        $this->except = $except;

        return $this;
    }
}
