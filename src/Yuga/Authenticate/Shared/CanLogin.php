<?php
namespace Yuga\Authenticate\Shared;

use Yuga\Validate\Message;
use Yuga\Database\Elegant\Model;
use Yuga\Authenticate\Exceptions\FieldNameMisMatch;

trait CanLogin
{
    protected function checkLoginFields($envLoginUsername, $envLoginPassword)
    {
        $fieldsArray = array_keys($this->request->getInput()->all());

        if (!in_array($envLoginUsername, $fieldsArray)) {
            throw new FieldNameMisMatch("The Field Name {$envLoginUsername} in the .env doesn't match the one from the form");
        }

        if (!in_array($envLoginPassword, $fieldsArray)) {
            throw new FieldNameMisMatch("The Field Name {$envLoginPassword} in the .env doesn't match the one from the form");
        }

        return true;
    }

    protected function checkValidators($loginFormUsernameField, $loginFormPasswordField, $usernameValue, $passwordValue, $remember = null)
    {
        // model fields
        $fields                 = explode(',', env('AUTH_MODEL_USERNAME_FIELDS', 'username'));
        $modelPasswordField     = env('AUTH_MODEL_PASSWORD_FIELD', 'password');

        return $this->checkUserName($this->model, $fields, $usernameValue, $loginFormUsernameField, $passwordValue, $loginFormPasswordField, $modelPasswordField, $remember);
    }

    protected function checkUserName(Model $model, array $fields, $username, $loginFormUsernameField, $passwordValue, $loginFormPasswordField, $modelPasswordField, $remember = null)
    {
        $firstField = array_shift($fields);
        $login = $model->where($firstField, $username);
        if (count($fields) > 0) {
            foreach ($fields as $field) {
                $login->orWhere($field, $username);
            }
        }
        if (!$this->userNotFound($model, $firstField, $fields, $loginFormUsernameField) instanceof Message) {
            if ($fetched = $login->first()) { 
                if (!$this->verifyPassword($fetched, $passwordValue, $modelPasswordField, $loginFormUsernameField) instanceof Message) {
                    $this->session->login($fetched);

                    $remember = ($remember === 'on') ? true: false;
                    if ($remember) {
                        $this->rememberUser($fetched);
                    }
                    if ($this->request->isAjax()) 
                        return $this->validate->errors();
                    return true;
                }  else {
                    return $this->verifyPassword($fetched, $passwordValue, $modelPasswordField, $loginFormUsernameField);
                }
            }
        } else {
            return $this->userNotFound($model, $firstField, $fields, $loginFormUsernameField);
        }
    }

    protected function verifyPassword($user, $password, $passwordField, $loginFormPasswordField)
    {
        $this->hash->setAlgorithm($this->getAuthMethod());
        $crypt_password = $this->hash->password($password, $this->getSalt($user));
        
        $userPassword = $user->$passwordField;
        $this->validate->addRuleMessage('found', 'Password or and Username mismatch!');
        $this->validate->addRule('found', function ($field, $value, $args) use ($crypt_password) {
            return $crypt_password === $args;
        });
        $validation = $this->validate->validator([
            $loginFormPasswordField => [
                'found' => $userPassword,
            ]
        ]);
        
        event('on:authenticate', ['user' => $user]);
        if ($this->request->isAjax()) 
            return (!$validation->hasErrors()) ? true : $validation;
        return ($validation->passed()) ? true : false;
    }

    protected function userNotFound($user, $firstField, $fields, $loginFormUsernameField)
    {
        $this->validate->addRuleMessage('userfound', 'Username Does not exist');
        $this->validate->addRule('userfound', function($field, $value, $args) use ($user, $firstField, $fields) {
            $loginUser = $user->where($firstField, $value);

            if (count($fields) > 0) {
                foreach ($fields as $field) {
                    $loginUser->orWhere($field, $value);
                }
            }
            return ($loginUser->first())? : false;
        });
        
        $validation = $this->validate->validator([
            $loginFormUsernameField => [
                'userfound' => true,
            ]
        ]);
        if ($this->request->isAjax()) 
            return (!$validation->hasErrors()) ? true : $validation;
        return ($validation->passed()) ? true : false;
    }

    public function getSalt(Model $model)
    {

        $modelUserSalt = env('AUTH_MODEL_TOKEN_FIELD');
        $appSecret = env('APP_SECRET', 'NoApplicationSecret');
        if (is_null($modelUserSalt)) {
            $modelUserSalt = $appSecret;
        } else {
            $modelUserSalt = $model->$modelUserSalt?:$appSecret;
        }

        return $modelUserSalt;
    }

    protected function getAuthMethod()
    {
        return env('AUTH_MODEL_CRYPT_TYPE', 'crypt');
    }
}