<?php
namespace Yuga\Shared;

use App\ViewModels\Home;

trait Paradigm
{
    protected function getStyle()
    {
        return env('APP_PARADIGM', 'mvvm');
    }

    public function getHome()
    {
        $home = new Home;
        if ($this->getStyle() == 'mvc') {
            $home = 'home';
        }

        return $home;
    }
}