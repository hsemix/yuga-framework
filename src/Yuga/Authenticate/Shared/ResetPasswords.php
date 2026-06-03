<?php
namespace Yuga\Authenticate\Shared;

use Yuga\Hash\Hash;
use App\Models\User;
use Yuga\Models\Auth;
use Yuga\Http\Request;
use Yuga\Shared\Paradigm;
use Yuga\Models\PasswordReset;
use App\ViewModels\ResetPassword;

trait ResetPasswords
{
    use RedirectUser, Paradigm;

    /**
     * Return the appropriete view basing on the app settings in .env
     *
     *
     * @return string|ViewModel $view
     */
    public function getViewFile(array $data)
    {
        return $this->getStyle() == 'mvc' ? 'auth.passwords.reset' : new ResetPassword($data);
    }

    /**
     * Show the application's reset password form.
     *
     * @param  string|null  $token
     *
     * @return \Yuga\View\ViewModel|\Yuga\Views\View
     */
    public function showResetForm(Request $request, PasswordReset $reset, $token)
    {
        $email = $reset->findByToken($token)->first()->email;
        $data = ['token' => $token, 'email' => $email];
        return view($this->getViewFile($data), $data);
    }

    /**
     * Make sure the email supplied is a valid and saved email-address
     *
     *
     * @return array
     */
    protected function validateFields(Request $request, Auth $auth)
    {
        $model = new User;
        $validated = $request->validate($this->rules());
        $auth->verifyEmail($model, $validated['email']);
        return ['validated' => $validated, 'auth' => $auth];
    }

    /**
     * Reset the user's password.
     *
     *
     * @return mixed
     */
    public function reset(Request $request, Auth $auth)
    {
        $this->saveReset($validated = $this->validateFields($request, $auth));
        $auth->login($request->get('email'), $request->get('password'));
        return $this->passwordChanged($request) ?: $this->redirectUser($validated);
    }

    /**
     * Get the password reset validation rules.
     * 
     * @param null
     *
     * @return array
     */
    protected function rules()
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ];
    }

    /**
     * Save the new password associated with the current user
     *
     *
     * @return \App\Models\User
     */
    protected function saveReset(array $values)
    {
        extract($values);
        $user = User::findByEmail($validated['email'])->first();
        $salt = $auth->getSalt($user);
        $password = (new Hash)->password($validated['password'], $salt);

        $user->save(['password' => $password]);
        return $user;
    }

    /**
     * Redirect user to an appropriate page and give them a message
     *
     *
     * @return \Yuga\Http\Redirect
     */
    protected function redirectUser(array $fields)
    {
        return redirect($this->redirectPath());
    }

    /**
     * The user has successfully changed their password.
     *
     * @param  mixed  $user
     * @return mixed
     */
    protected function passwordChanged(Request $request)
    {

    }
}
