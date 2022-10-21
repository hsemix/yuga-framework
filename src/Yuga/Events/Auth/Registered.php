<?php

namespace Yuga\Events\Auth;

use Yuga\Events\Event;
use Yuga\Models\User;

class Registered extends Event
{
    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
