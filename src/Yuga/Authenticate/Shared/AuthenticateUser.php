<?php

namespace Yuga\Authenticate\Shared;

use Auth;
use Yuga\Http\Request;
use Yuga\Http\Response;
use Yuga\Events\Auth\Registered;

trait AuthenticateUser
{
    use RedirectUser;
    /**
     * Show the application's login form.
     *
     * @return \Yuga\Http\Response
     */
    public function showLoginForm()
    {
        $this->middleware('remembered');
        return view('auth.login');
    }

    /**
     * Handle a login request to the application. Saves number of login attemps and returns appropriate response
     *
     * @param  \Yuga\Http\Request  $request
     * @return \Yuga\Http\RedirectResponse|\Yuga\Http\Response|\Yuga\Http\JsonResponse
     *
     */
    public function login(Request $request, Response $response, Auth $auth)
    {
        $login = $auth->login($request->get($this->formFields['username']), $request->get($this->formFields['password']), $request->get($this->formFields['remember']));
        return $this->loggedIn($request, $auth->user()) ?: redirect($this->redirectPath());
    }
    /**
     * The user has been logged in.
     *
     * @param  \Yuga\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function loggedIn(Request $request, $user)
    {

    }

    public function logout(Auth $auth)
    {
        $auth->logout();
        return redirect('/login');
    }
}