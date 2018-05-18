<?php

namespace Yuga\Authenticate\Shared;

use Yuga\Http\Request;
use Yuga\Session\Session;
use App\Events\Registered;
use App\Handlers\EmailConfirmation;
use Yuga\EventHandlers\Auth\OtherwiseHandler;
use Yuga\Events\Auth\EmailConfirmation as OtherwiseEvent;

trait RegisterUser
{
    use RedirectUser;

    /**
     * Show the application registration form.
     *
     * @return \Yuga\Http\Response
     */
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Yuga\Http\Request  $request
     * @return \Yuga\Http\Response
     */
    public function register(Request $request, Session $auth)
    {
        $this->validator($request);
        $eventClass = OtherwiseEvent::class;
        $handleClass = OtherwiseHandler::class;

        if (class_exists(Registered::class)) {
            $eventClass = Registered::class;
        }

        if (class_exists(EmailConfirmation::class)) {
            $handleClass = EmailConfirmation::class;
        }
        (new $eventClass($user = $this->create($request->all())))->attach(new $handleClass)->dispatch();

        $auth->login($user);

        return $this->registered($request, $user) ?: redirect(host($this->redirectPath()));
    }

    /**
     * The user has been registered.
     *
     * @param  \Yuga\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function registered(Request $request, $user)
    {
        //
    }
}
