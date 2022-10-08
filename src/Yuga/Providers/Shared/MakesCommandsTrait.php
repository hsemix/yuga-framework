<?php

namespace Yuga\Providers\Shared;

use Yuga\Application\Application;

trait MakesCommandsTrait
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }
}