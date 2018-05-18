<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Widgets;
use Yuga\Models\User;
class SignUp extends Framework
{
    public function __construct()
    {
        parent::__construct();

        /**
         * Check if the router has Route::form on the uri and handle the post as well
         */
        if ($this->isPostBack()) {
            $validation = $this->validate([
                'fullname' => 'required|min:4',
                'email' => 'required|email|unique:users',
                'username' => 'required|unique:users',
                'password' => 'required|min:6',
                'password_confirmation' => 'required|matches:password',
            ]);
            $this->session->login($this->saveUser($validation));
            $this->response->redirect->route('yuga.welcome');
        }
    }

    /**
     * Save the User model with given fields and values returned from the validator
     * @param \array $fields
     * @return \Yuga\Models\User
     */

    protected function saveUser(array $fields)
    {
        return User::create([
            'email' => $fields['email'],
            'fullname' => $fields['fullname'],
            'username' => $fields['username'],
            'password' => $this->hash->setAlgorithm('crypt')->password($fields['password'])
        ]);
    }
}