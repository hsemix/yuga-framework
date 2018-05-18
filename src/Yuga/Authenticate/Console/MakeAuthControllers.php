<?php
namespace Yuga\Authenticate\Console;

trait MakeAuthControllers
{
    protected function createAuthControllers()
    {
        $this->createFolders();
        $this->createHomeController();
        $this->createLoginController();
        $this->createRegisterController();
        $this->createResetPasswordController();
        $this->createForgotPasswordController();
    }

    protected function createFolders()
    {
        if (!is_dir($directory = path('app/Controllers/Auth'))) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Creates the HomeController temp.
     *
     * @return void
     */
    protected function createHomeController()
    {
        file_put_contents(
            path('app/Controllers/HomeController.php'),
            $this->compileHomeControllerTemp()
        );
    }

    /**
     * Creates the LoginController temp.
     *
     * @return void
     */
    protected function createLoginController()
    {
        file_put_contents(
            path('app/Controllers/Auth/LoginController.php'),
            $this->compileLoginControllerTemp()
        );
    }

    /**
     * Creates the RegisterController temp.
     *
     * @return void
     */
    protected function createRegisterController()
    {
        file_put_contents(
            path('app/Controllers/Auth/RegisterController.php'),
            $this->compileRegisterControllerTemp()
        );
    }

    /**
     * Creates the ResetPasswordController temp.
     *
     * @return void
     */
    protected function createResetPasswordController()
    {
        file_put_contents(
            path('app/Controllers/Auth/ResetPasswordController.php'),
            $this->compileResetPasswordControllerTemp()
        );
    }

    /**
     * Creates the ForgotPasswordController temp.
     *
     * @return void
     */
    protected function createForgotPasswordController()
    {
        file_put_contents(
            path('app/Controllers/Auth/ForgotPasswordController.php'),
            $this->compileForgotPasswordControllerTemp()
        );
    }

    /**
     * Compiles the HomeController temp.
     *
     * @return string
     */
    protected function compileHomeControllerTemp()
    {
        return str_replace(
            '{namespace}',
            env('APP_NAMESPACE', 'App'),
            file_get_contents(__DIR__.'/temps/make/controllers/HomeController.temp')
        );
    }

    /**
     * Compiles the LoginController temp.
     *
     * @return string
     */
    protected function compileLoginControllerTemp()
    {
        return str_replace(
            '{namespace}',
            env('APP_NAMESPACE', 'App'),
            file_get_contents(__DIR__.'/temps/make/controllers/LoginController.temp')
        );
    }

    /**
     * Compiles the RegisterController temp.
     *
     * @return string
     */
    protected function compileRegisterControllerTemp()
    {
        return str_replace(
            '{namespace}',
            env('APP_NAMESPACE', 'App'),
            file_get_contents(__DIR__.'/temps/make/controllers/RegisterController.temp')
        );
    }

    /**
     * Compiles the ResetPasswordController temp.
     *
     * @return string
     */
    protected function compileResetPasswordControllerTemp()
    {
        return str_replace(
            '{namespace}',
            env('APP_NAMESPACE', 'App'),
            file_get_contents(__DIR__.'/temps/make/controllers/ResetPasswordController.temp')
        );
    }

    /**
     * Compiles the ForgotPasswordController temp.
     *
     * @return string
     */
    protected function compileForgotPasswordControllerTemp()
    {
        return str_replace(
            '{namespace}',
            env('APP_NAMESPACE', 'App'),
            file_get_contents(__DIR__.'/temps/make/controllers/ForgotPasswordController.temp')
        );
    }
}