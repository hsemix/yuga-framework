<?php
namespace Yuga\Authenticate\Shared;

use Yuga\Events\Event;
use Yuga\Http\Request;
use Yuga\Session\Session;
use Yuga\Shared\Paradigm;
use App\ViewModels\Register;
use App\Handlers\EmailConfirmation;
use Yuga\EventHandlers\Auth\EmailConfirmation as OtherwiseHandler;

trait RegisterUser
{
    use RedirectUser, Paradigm;

    /**
     * Show the application registration form.
     *
     * @return \Yuga\Http\Response
     */
    public function showRegistrationForm()
    {
        return view($this->getView());
    }

    /**
     * Return the appropriete view basing on the app settings in .env
     * 
     * @return string|ViewModel $view
     */
    public function getView()
    {
        if ($this->getStyle() == 'mvc') {
            $view = 'auth.register';
        } else {
            $view = new Register;
        }
        return $view;
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

        $eventClass = Event::class;
       
        $handleClass = OtherwiseHandler::class;

        if (class_exists(EmailConfirmation::class)) {
            $handleClass = EmailConfirmation::class;
        }
        (new $eventClass($user = $this->create($request->all())))->attach(new $handleClass)->dispatch();

        $auth->login($user);

        return $this->registered($request, $user) ?: redirect(route($this->redirectPath()));
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
