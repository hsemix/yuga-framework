<?php
namespace Yuga\Shared;

use App\ViewModels\Home;
use App\ViewModels\ForgotPasswordEmail;

trait Paradigm
{
    /**
     * Get the app's programming style
     * 
     * @param null
     * 
     * @return string
     */
    protected function getStyle()
    {
        return env('APP_PARADIGM', 'mvvm');
    }

    /**
     * Get the Home page of the applicatino
     * 
     * @param null
     * 
     * @return string|\Yuga\View\ViewModel
     */
    public function getHome()
    {
        if ($this->getStyle() == 'mvc') {
            $home = 'home';
        } else {
            $home = new Home;
        }

        return $home;
    }

    /**
     * Get the Email file for password resets
     * 
     * @param null
     * 
     * @return string|\Yuga\View\ViewModel
     */
    public function getMailFile(array $params = null)
    {
        if ($this->getStyle() == 'mvc') {
            $forgotPassword = 'mailables.forgot-password';
        } else {
            $forgotPassword = new ForgotPasswordEmail($params);
        }

        return $forgotPassword;
    }
}