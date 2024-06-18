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
        $this->createForgotPasswordViewModel();
        $this->createForgotPasswordEmailViewModel();
        $this->createResetPasswordViewModel();
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
     * Creates the ForgotPassword ViewModel temp.
     *
     * @return void
     */
    protected function createForgotPasswordViewModel()
    {
        file_put_contents(
            path('app/ViewModels/ForgotPassword.php'),
            $this->compileForgotPasswordViewModelTemp()
        );
    }

    /**
     * Creates the ForgotPasswordEmail ViewModel temp.
     *
     * @return void
     */
    protected function createForgotPasswordEmailViewModel()
    {
        file_put_contents(
            path('app/ViewModels/ForgotPasswordEmail.php'),
            $this->compileForgotPasswordEmailViewModelTemp()
        );
    }

    /**
     * Creates the ResetPassword ViewModel temp.
     *
     * @return void
     */
    protected function createResetPasswordViewModel()
    {
        file_put_contents(
            path('app/ViewModels/ResetPassword.php'),
            $this->compileResetPasswordViewModelTemp()
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
     * Compiles the ForgotPasswordViewModel temp.
     *
     * @return string
     */
    protected function compileForgotPasswordViewModelTemp()
    {
        return str_replace(
            '{namespace}',
            env('APP_NAMESPACE', 'App'),
            file_get_contents(__DIR__.'/temps/make/viewModels/ForgotPassword.temp')
        );
    }

    /**
     * Compiles the ForgotPasswordEmailViewModel temp.
     *
     * @return string
     */
    protected function compileForgotPasswordEmailViewModelTemp()
    {
        return str_replace(
            '{namespace}',
            env('APP_NAMESPACE', 'App'),
            file_get_contents(__DIR__.'/temps/make/viewModels/ForgotPasswordEmail.temp')
        );
    }

    /**
     * Compiles the ResetPasswordViewModel temp.
     *
     * @return string
     */
    protected function compileResetPasswordViewModelTemp()
    {
        return str_replace(
            '{namespace}',
            env('APP_NAMESPACE', 'App'),
            file_get_contents(__DIR__.'/temps/make/viewModels/ResetPassword.temp')
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