<?php
namespace Yuga\Authenticate\Shared;

use Yuga\Database\Elegant\Model;

trait CanBeRemembered
{
    /**
     * Remember the user or save the user's setting that they want to be remembered
     *
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
     *
     */
    protected function checkRemember(Model $model)
    {
        $hash = $model->remember_token != '' ? $model->remember_token : $this->hash->unique();
        $model->save(['remember_token' => $hash]);
        $this->cookie->put($this->settings->get('remember.name'), $hash, $this->settings->get('remember.expiry'));
    }
}