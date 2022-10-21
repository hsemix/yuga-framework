<?php

namespace Yuga\Controllers;

use Yuga\Widgets\Welcome;

class WelcomeController extends Controller
{
    public function getWelcome()
    {
        echo new Welcome('Yuga Framework');
    }
}
