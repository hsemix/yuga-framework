<?php

namespace Yuga\Authenticate;

use Closure;
use Yuga\Models\Auth;
use Yuga\Http\Request;
use Yuga\Validate\Message;
use Yuga\Shared\Controller;
use Yuga\Http\Middleware\IMiddleware;
use Yuga\Authenticate\Shared\CanLogin;
use Yuga\Authenticate\LoginWithRemember;
use Yuga\Authenticate\Shared\CanBeRemembered;
use Yuga\Authenticate\Shared\CanResetPassword;
use Yuga\Controllers\Controller as BaseController;

class Authenticate extends BaseController implements IMiddleware
{
    use Controller, CanLogin, CanBeRemembered, CanResetPassword;
    protected $model;
    protected $settings;
    public function __construct()
    {
        $model = env('AUTH_MODEL', \Yuga\Models\User::class);
        $this->model = new $model;
        $this->init();
        $this->settings = $this->app->config->load('config.Settings');
    }

    /**
     * Run only the routes provided
     * 
     * @param array | [] $routes
     * 
     * @return static
     */
    public function except(array $routes = [])
    {
        return in_array(ltrim(request()->getUri(), '/'), $routes);
    }

    public function user()
    {
        $model = $this->model;

        if ($this->session->isLoggedIn()) {
            return $model->find($this->session->user_id);
        } else {
            if (method_exists($model, 'access')) {
                try {
                    $token = request()->getBearerToken();
                    return $model->access($token);
                } catch(\Exception $e) {
                    return $model;
                }
            }
            return $model;
        }
    }

    public function login($username, $password, $remember = null)
    {        
        // form fields
        $loginFormUsernameField = env('AUTH_FORM_USERNAME_FIELD', 'username');
        $loginFormPasswordField = env('AUTH_FORM_PASSWORD_FIELD', 'password');

        
        if ($this->checkLoginFields($loginFormUsernameField, $loginFormPasswordField)) {
            $validation = $this->validate->validator([
                $loginFormUsernameField => 'required',
                $loginFormPasswordField => 'required',
            ]);

            return $this->checkValidators($loginFormUsernameField, $loginFormPasswordField, $username, $password, $remember); 
        }
    }

    public function logout()
    {
        event('on:signout', ['user' => $this->user()]);
        if (!is_null($this->user()->remember_token) && $this->cookie->exists($this->settings->get('remember.name'))) {
            $this->user()->save([
                'remember_token' => null
            ]);

            $this->cookie->delete($this->settings->get('remember.name'));
        }
        $this->session->logout();
    }

    public function guest()
    {
        return !$this->session->isLoggedIn();
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
        
        if ($this->guest()) {

            if ($this->guest()) {
                session()->put('yuga-user-link-session', $request->getUri());
                if (Auth::authRoutesExist()) {
                    $this->response->redirect('login');
                }
    
                return $this->response->redirect(env('DEFAULT_LOGIN_REDIRECT', route('login')));
                exit();
            } else {
                if (session()->exists('yuga-user-link-session')) {
                    $uri = session('yuga-user-link-session');
                    session()->delete('yuga-user-link-session');
    
                    return $this->response->redirect($uri);
                    exit();
                }
            }
        }

        return $next($request);
    }

}
