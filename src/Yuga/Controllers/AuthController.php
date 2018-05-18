<?php
namespace Yuga\Controllers;

use Yuga\Widgets\Reset;
use Yuga\Widgets\SignIn;
use Yuga\Widgets\SignUp;

class AuthController extends Controller
{
    public function getSignin()
    {
        echo new SignIn;
    }

    public function getSignup()
    {
        echo new SignUp;
    }

    public function getSignout()
    {
        \Auth::logout();
        $this->response->redirect->route('yuga.welcome');
    }

    public function getReset(\Yuga\Mailables\Mailer $mailer)
    {
        echo new Reset($mailer);
    }
}