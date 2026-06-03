<?php

declare(strict_types=1);

namespace Yuga\Events\Auth;

use Yuga\Models\User;
use Yuga\Events\Event;

class Registered extends Event
{
    /**
     * @var \Yuga\Models\User
     */
    public $user; 
    
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}
