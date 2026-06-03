<?php

declare(strict_types=1);

namespace Yuga\Database\Query;

class Raw implements \Stringable
{

    protected string $value;

    protected array $bindings;

    /**
     * Raw constructor.
     * @param string $value
     * @param array|string $bindings
     */
    public function __construct($value, $bindings = [])
    {
        $this->value = (string)$value;
        $this->bindings = (array)$bindings;
    }

    public function getBindings()
    {
        return $this->bindings;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function where()
    {
        return $this;
    }
}
