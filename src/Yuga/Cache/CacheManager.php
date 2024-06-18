<?php

namespace Yuga\Cache;

class CacheManager implements CacheDriver
{
    protected $stores = [];

    protected $defaultStoreName;

    public function setDefaultStoreName(string $name)
    {
        if (!isset($this->stores[$name])) {
            throw new \Exception('Store `' . $name . '` was not defined.');
        }

        $this->defaultStoreName = $name;

        return $this;
    }

    public function getDefaultStoreName()
    {
        return $this->defaultStoreName;
    }

    public function register($name, callable $callable, bool $default = false)
    {
        $this->stores[$name] = $callable;

        if ($default) {
            $this->setDefaultStoreName($name);
        }

        return $this;
    }

    public function registerDriver($name, CacheDriver $driver, bool $default = false)
    {
        $this->stores[$name] = $driver;

        if ($default) {
            $this->setDefaultStoreName($name);
        }

        return $this;
    }

    public function store(?string $name = null): CacheDriver
    {
        if (!$name = $name ?: $this->defaultStoreName) {
            throw new \Exception('Default cache driver was not defined.');
        }

        if (!$driver = $this->stores[$name] ?? null) {
            throw new \Exception('Cache driver `' . $name . '` was not defined.');
        }

        if (is_callable($driver)) {
            $driver = call_user_func_array($driver, []);

            if (!($driver instanceof CacheDriver)) {
                throw new \Exception('Cache driver `' . $name . '` must be an instance of `' . CacheDriver::class . '`');
            }

            $this->stores[$name] = $driver;
        }

        return $this->stores[$name];
    }

    public function has(string $key): bool
    {
        return $this->store()->has($key);
    }

    public function hasMultiple(array $keys): bool
    {
        return $this->store()->hasMultiple($keys);
    }

    public function get(string $key, $default = null)
    {
        return $this->store()->get($key, $default);
    }

    public function getMultiple(array $keys, $default = null)
    {
        return $this->store()->getMultiple($keys, $default);
    }

    public function set(string $key, $value, ?int $ttl = null)
    {
        $this->store()->set($key, $value, $ttl);

        return $this;
    }

    public function setMultiple(array $values, ?int $ttl = null)
    {
        $this->store()->setMultiple($values, $ttl);

        return $this;
    }

    public function forever(string $key, $value)
    {
        $this->store()->forever($key, $value);

        return $this;
    }

    public function foreverMultiple(array $values)
    {
        $this->store()->foreverMultiple($values);

        return $this;
    }

    public function delete(string $key)
    {
        $this->store()->delete($key);

        return $this;
    }

    public function deleteMultiple(array $keys)
    {
        $this->store()->deleteMultiple($keys);

        return $this;
    }

    public function clear()
    {
        $this->store()->clear();

        return $this;
    }

    public function remember(string $key, callable $callable, ?int $ttl = null)
    {
        return $this->store()->remember($key, $callable, $ttl);
    }

    public function increment(string $key, int $amount = 1, ?int $ttl = null)
    {
        $this->store()->increment($key, $amount, $ttl);

        return $this;
    }

    public function decrement(string $key, int $amount = 1, ?int $ttl = null)
    {
        $this->store()->decrement($key, $amount, $ttl);
    }

    public function incrementFloat(string $key, float $amount = 1.0, ?int $ttl = null)
    {
        $this->store()->incrementFloat($key, $amount, $ttl);

        return $this;
    }

    public function decrementFloat(string $key, float $amount = 1.0, ?int $ttl = null)
    {
        $this->store()->decrementFloat($key, $amount, $ttl);

        return $this;
    }

    public function touch(string $key, ?int $ttl = null)
    {
        $this->store()->touch($key, $ttl);

        return $this;
    }

    public function pull(string $key, $default = null)
    {
        return $this->store()->pull($key, $default);
    }

    public function add(string $key, $value, ?int $ttl = null): bool
    {
        return $this->store()->add($key, $value, $ttl);
    }
}
