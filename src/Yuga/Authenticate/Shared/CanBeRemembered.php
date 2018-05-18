<?php
namespace Yuga\Authenticate\Shared;

use Yuga\Database\Elegant\Model;

trait CanBeRemembered
{
    /**
     * Remember the user or save the user's setting that they want to be remembered
     * 
     * @param \Yuga\Database\Elegant\Model $model
     * 
     * @return any
     */
    protected function rememberUser(Model $model)
    {
       return $this->checkRemember($model);
    }

    /**
     * Check whether or not the user has asked to be remembered before
     * 
     * @param \Yuga\Database\Elegant\Model $model
     * 
     * @return null
     */
    protected function checkRemember(Model $model)
    {
        if ($model->remember_token != '') {
            $hash = $model->remember_token;
        } else {
            $hash = $this->hash->unique();
        }
        $model->save(['remember_token' => $hash]);
        $this->cookie->put($this->settings->get('remember.name'), $hash, $this->settings->get('remember.expiry'));
    }
}