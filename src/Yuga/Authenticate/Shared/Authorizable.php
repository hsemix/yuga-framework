<?php

namespace Yuga\Authenticate\Shared;

use Yuga\App;
use Yuga\Authorize\AuthorizeUser;

trait Authorizable
{
    /**
     * Determine if the entity has a given ability.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function can($ability, $arguments = [])
    {
        $authorize = App::resolve(AuthorizeUser::class)->forUser($this);

        return $authorize->check($ability, $arguments);
    }

    /**
     * Determine if the entity does not have a given ability.
     *
     * @param  string  $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function cannot($ability, $arguments = [])
    {
        return !$this->can($ability, $arguments);
    }
}