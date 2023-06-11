<?php

namespace Yuga\Pipeline;

use Closure;
use InvalidArgumentException;

class Pipeline
{
    protected $passable;
    protected $pipes;
    protected $method = 'run';

    public static function send($passable)
    {
        $pipeline = new static;

        $pipeline->passable = $passable;

        return $pipeline;
    }

    public function through(array $pipes)
    {
        $this->pipes = $pipes;

        return $this;
    }

    public function then(Closure $destination)
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes),
            $this->carry(),
            function ($passable) use ($destination) {
                return $destination($passable);
            }
        );

        return $pipeline($this->passable);
    }

    public function thenReturn()
    {
        return $this->then(function ($passable) {
            return $passable;
        });
    }

    protected function carry()
    {
        return function ($pipeStack, $pipe) {
            return function ($passable) use ($pipeStack, $pipe) {
                
                if (is_callable($pipe)) {
                    return $pipe($passable, $pipeStack);
                } elseif (is_object($pipe)) {
                    return $pipe->{$this->method}($passable, $pipeStack);
                } elseif (is_string($pipe) && class_exists($pipe)) {

                    $pipeInstance = app($pipe);

                    return $pipeInstance->{$this->method}($passable, $pipeStack);
                } else {
                    throw new InvalidArgumentException('Invalid pipe type.');
                }
            };
        };
    }
}