<?php
namespace Yuga\Authenticate;

use Closure;
use Yuga\Application;
use Yuga\Models\User;
use Yuga\Http\Request;
use Yuga\Cookie\Cookie;
use Yuga\Session\Session;
use Yuga\Http\Middleware\IMiddleware;

class LoginWithRemember implements IMiddleware
{
    protected $app;
    protected $user;
    protected $cookie;
    protected $session;
    
    /**
     * -------------------------------------------------------------------------
     * Inject any objects (in the contructor) you want to use in this middleware
     * We will worry about instantiating them for you
     * -------------------------------------------------------------------------
     */
    public function __construct(Session $session, Cookie $cookie, User $user, Application $app)
    {
        $this->app      = $app;
        $this->user     = $user;
        $this->cookie   = $cookie;
        $this->session  = $session;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Yuga\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function run(Request $request, Closure $next)
    {
        $settings = $this->app->config->load('config.Settings');
        if($this->cookie->exists($settings->get('remember.name')) && !$this->session->isLoggedIn()){
            $hash = $this->cookie->get($settings->get('remember.name'));
            if($hashCheck = $this->user->where('remember_token', $hash)->first()){
                $this->session->login($hashCheck);
            }
        }

        return $next($request);
    }
}