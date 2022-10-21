<?php

namespace Yuga\Authenticate\Shared;

use App\Models\User;
use App\ViewModels\ForgotPassword;
use Yuga\Hash\Hash;
use Yuga\Http\Request;
use Yuga\Models\Auth;
use Yuga\Models\PasswordReset;
use Yuga\Shared\Paradigm;

trait SendPasswordResetEmails
{
    use RedirectUser;
    use Paradigm;

    /**
     * Return the appropriete view basing on the app settings in .env.
     *
     * @return string|ViewModel $view
     */
    public function getView()
    {
        if ($this->getStyle() == 'mvc') {
            $view = 'auth.passwords.email';
        } else {
            $view = new ForgotPassword();
        }

        return $view;
    }

    /**
     * Show the application's request email form.
     *
     * @return \Yuga\Http\Response
     */
    public function showEmailForm()
    {
        return view($this->getView());
    }

    /**
     * Send settings on how the user can reset their password to their email-address.
     *
     * @param \Yuga\Http\Request $request
     * @param Auth               $auth
     *
     * @return mixed
     */
    public function sendEmail(Request $request, Auth $auth)
    {
        $passwordReset = $this->saveReset($validated = $this->validateEmail($request, $auth));
        $this->sendMail($validated, $passwordReset);

        return $this->emailSent($request) ?: $this->redirectUser($validated);
    }

    /**
     * Redirect user to an appropriate page and give them a message.
     *
     * @param array $fields
     *
     * @return \Yuga\Http\Redirect
     */
    protected function redirectUser(array $fields)
    {
        app()->make('session')->flash('email-sent', 'Details on how you can reset your password have been sent to <strong>'.$fields['email'].'</strong>');

        return redirect($this->redirectPath());
    }

    /**
     * The user has received a reset password email.
     *
     * @param \Yuga\Http\Request $request
     * @param mixed              $user
     *
     * @return mixed
     */
    protected function emailSent(Request $request)
    {
    }

    /**
     * Make sure the email supplied is a valid and saved email-address.
     *
     * @param \Yuga\Http\Request $request
     * @param Auth               $auth
     *
     * @return array $validation
     */
    protected function validateEmail(Request $request, Auth $auth)
    {
        $model = new User();
        $validation = $request->validate([
            'email' => 'required|email',
        ]);
        $auth->verifyEmail($model, $validation['email']);

        return $validation;
    }

    /**
     * Save the reset settings of the user in a table for later use.
     *
     * @param array $fields
     *
     * @return \Yuga\Models\PasswordReset|\App\Models\PasswordReset
     */
    protected function saveReset($fields)
    {
        return PasswordReset::create([
            'email' => $fields['email'],
            'token' => (new Hash('sha256'))->unique(),
        ]);
    }
}
