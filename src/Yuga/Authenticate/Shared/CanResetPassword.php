<?php
namespace Yuga\Authenticate\Shared;

use Yuga\Models\ElegantModel as Model;

trait CanResetPassword
{
    protected $model;
    protected function verifyEmail(Model $model, $email, $fieldName = 'email')
    {
        $this->validate->addRuleMessage('findUser', 'Email Address Doesnot Exist!');
        $this->validate->addRule('findUser', function($field, $value, $args) use ($model, $fieldName) {
            $loginUser = $model->where($fieldName, $value);
            return ($loginUser->first())? : false;
        });
        
        $validation = $this->validate->validator([
            $fieldName =>'findUser',
        ]);
        if ($this->request->isAjax()) 
            return (!$validation->hasErrors()) ? true : $validation;
        return ($validation->passed()) ? true : false;
    }   
}