<?php
namespace Yuga\Authenticate\Console;

trait MakeAuthViewModels
{
    protected function createViewModels()
    {
        $this->createViewModelFolders();
        $this->createHomeViewModel();
        $this->createLoginViewModel();
        $this->createRegisterViewModel();
    }

    protected function createViewModelFolders()
    {
        if (!is_dir($directory = path('app/ViewModels'))) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Creates the Home ViewModel temp.
     *
     * @return void
     */
    protected function createHomeViewModel()
    {
        file_put_contents(
            path('app/ViewModels/Home.php'),
            $this->compileHomeViewModelTemp()
        );
    }

    /**
     * Creates the Login ViewModel temp.
     *
     * @return void
     */
    protected function createLoginViewModel()
    {
        file_put_contents(
            path('app/ViewModels/Login.php'),
            $this->compileLoginViewModelTemp()
        );
    }

    /**
     * Creates the Register ViewModel temp.
     *
     * @return void
     */
    protected function createRegisterViewModel()
    {
        file_put_contents(
            path('app/ViewModels/Register.php'),
            $this->compileRegisterViewModelTemp()
        );
    }

    /**
     * Compiles the HomeViewModel temp.
     *
     * @return string
     */
    protected function compileHomeViewModelTemp()
    {
        return str_replace(
            '{namespace}',
            env('APP_NAMESPACE', 'App'),
            file_get_contents(__DIR__.'/temps/make/viewModels/Home.temp')
        );
    }

    /**
     * Compiles the RegisterViewModel temp.
     *
     * @return string
     */
    protected function compileRegisterViewModelTemp()
    {
        return str_replace(
            '{namespace}',
            env('APP_NAMESPACE', 'App'),
            file_get_contents(__DIR__.'/temps/make/viewModels/Register.temp')
        );
    }

    /**
     * Compiles the LoginViewModel temp.
     *
     * @return string
     */
    protected function compileLoginViewModelTemp()
    {
        return str_replace(
            '{namespace}',
            env('APP_NAMESPACE', 'App'),
            file_get_contents(__DIR__.'/temps/make/viewModels/Login.temp')
        );
    }
}