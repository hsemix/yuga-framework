<?php
/**
 * @author Mahad Tech Solutions
 */

namespace Yuga\Validate;

use Yuga\Interfaces\Application\Application;
use Yuga\Providers\ServiceProvider;

class ValidateServiceProvider extends ServiceProvider
{
    public function load(Application $app)
    {
        $app->bind('validate', Validate::class);

        return $app->resolve('validate');
    }
}
