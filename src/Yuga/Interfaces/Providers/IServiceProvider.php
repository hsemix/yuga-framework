<?php

declare(strict_types=1);

namespace Yuga\Interfaces\Providers;

use Yuga\Interfaces\Application\Application;

interface IServiceProvider
{
    public function register(Application $app);
}
