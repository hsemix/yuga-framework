<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Widgets;
use Yuga\Models\User;
use Yuga\Mailables\Mailer;
use Yuga\Models\PasswordReset;
use Yuga\Authenticate\Shared\CanResetPassword;
class Reset extends Framework
{
    use CanResetPassword;
    protected $mailer;
    protected $model;
    public function __construct(Mailer $mailer = null)
    {
        parent::__construct();
        $model = new User;
        $this->model = $model;
        $this->mailer = $mailer;
        /**
         * Check if the router has Route::form on the uri and handle the post request as well
         */
        if ($this->isPostBack()) {
            $validation = $this->validate([
                'email' => 'required|email'
            ]);
            $this->verifyEmail($model, $validation['email']);
            $passwordReset = $this->saveReset($validation);
            $this->sendMail($validation, $passwordReset);
            return $this->response->redirect->refresh();
        }  
    }

    protected function saveReset($fields)
    {
        return PasswordReset::create([
            'email' => $fields['email'],
            'token' => $this->hash->unique()
        ]);
    }

    protected function sendMail($fields, $passwordReset)
    {
        $user = $this->model->findByEmail($fields['email'])->first();
        return $this->mailer->send('mailables.test', ['name' => $user->fullname, 'code' => $passwordReset->token], function($m) use ($fields) {
            $m->to($fields['email']);
            $m->subject('Reset Email');
            $m->from('noreply@yuga.com'); // optional
        });
    }
}