<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Widgets;
use Yuga\View\ViewModel;
use Yuga\Views\Widgets\Menu\Menu;
abstract class Framework extends ViewModel
{
    protected $applicationMenu;
    public function __construct()
    {
        parent::__construct();
        $this->name = 'Yuga Framework';
        $this->getSite()->setTitle('Welcom to ' . $this->name);
        $this->getSite()->addCss(assets('yuga/bootstrap/css/bootstrap.min.css'));
        $this->getSite()->addCss(assets('yuga/fonts/css/font-awesome.min.css'));
        $this->getSite()->addCss(assets('yuga/css/yuga.css'));
        $this->getSite()->addJs(assets('yuga/js/jQuery/jquery-2.2.3.min.js'));
        $this->getSite()->addJs(assets('yuga/bootstrap/js/bootstrap.min.js'));
        $this->makeMenu();
    }

    protected function makeMenu()
    {
        $this->applicationMenu = new Menu();
        $this->applicationMenu->addClass('nav navbar-nav');
        $this->applicationMenu->addItem('Home', route('yuga.welcome'))->addClass('nav-item')->addLinkAttribute('class', 'nav-link');
        if (\Auth::guest()) {
            $this->applicationMenu->addItem('Login', route('yuga.auth.signin'))->addClass('nav-item')->addLinkAttribute('class', 'nav-link');
            $this->applicationMenu->addItem('Register', route('yuga.auth.signup'))->addClass('nav-item')->addLinkAttribute('class', 'nav-link');
            $this->applicationMenu->addItem('Reset Password', route('yuga.auth.reset'))->addClass('nav-item')->addLinkAttribute('class', 'nav-link');
        } else {
            $this->applicationMenu->addItem('Logout', route('yuga.auth.signout'))->addClass('nav-item')->addLinkAttribute('class', 'nav-link');
        }
    }
    protected function printMenu()
    {
        return $this->applicationMenu;
    }
}