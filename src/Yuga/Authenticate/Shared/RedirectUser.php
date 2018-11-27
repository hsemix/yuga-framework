<?php
namespace Yuga\Authenticate\Shared;

trait RedirectUser
{
    /**
     * Get the post register / login redirect path.
     *
     * @return string
     */
    public function redirectPath()
    {
        if (method_exists($this, 'redirectTo')) {
            return $this->redirectTo();
        }

        return property_exists($this, 'redirectTo') ? route($this->redirectTo) : route('home');
    }
}
