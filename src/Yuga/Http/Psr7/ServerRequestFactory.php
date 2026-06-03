<?php

namespace Yuga\Http\Psr7;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;

class ServerRequestFactory
{
    public static function fromGlobals(): ServerRequestInterface
    {
        $factory = new Psr17Factory();

        return (new ServerRequestCreator(
            $factory,
            $factory,
            $factory,
            $factory
        ))->fromGlobals();
    }
}