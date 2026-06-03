<?php
namespace Yuga\Authenticate;

use Closure;
use Yuga\Models\User;
use Yuga\Http\Request;
use Yuga\Cookie\Cookie;
use Yuga\Session\Session;
use Yuga\Application\Application;
use Yuga\Http\Middleware\IMiddleware;

class LoginWithRemember implements IMiddleware
{
    /**
     * -------------------------------------------------------------------------
     * Inject any objects (in the contructor) you want to use in this middleware
     * We will worry about instantiating them for you
     * -------------------------------------------------------------------------
     */
    public function __construct(protected \Yuga\Session\Session $session, protected \Yuga\Cookie\Cookie $cookie, protected \Yuga\Models\User $user, protected \Yuga\Application\Application $app)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function run(Request $request, Closure $next)
    {
        $settings = $this->app->config->load('config.Settings');
        if($this->cookie->exists($settings->get('remember.name')) && !$this->session->isLoggedIn()) {
            $hash = $this->cookie->get($settings->get('remember.name'));
            if($hashCheck = $this->user->where('remember_token', $hash)->first()) {
                $this->session->login($hashCheck);
            }
        }

        return $next($request);
    }
}