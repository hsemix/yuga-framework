<?php
namespace Yuga\Events\Auth;

use Yuga\Models\User;
use Yuga\Events\Event;

class Registered extends Event
{
    public $user; 
    public function __construct(User $user)
    {
        $this->user = $user;
    }
}