<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Widgets;

class SignIn extends Framework
{
    public function __construct()
    {
        parent::__construct();

        /**
        * Check if the router has Route::form on the uri and handle the post as well
        */
        if ($this->isPostBack()) {
            /*
                Validation can entiry be left out since Auth has it's own validation
                $validation = $this->validate([
                    'username' => 'required',
                    'password' => 'required',
                ]);
            */
            $auth = \Auth::login($this->request->get('username'), $this->request->get('password'), $this->request->get('remember_me'));

            $this->response->redirect->route('yuga.welcome');
        }
    }
}