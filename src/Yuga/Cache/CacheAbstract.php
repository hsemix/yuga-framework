<?php

namespace Yuga\Cache;

abstract class CacheAbstract implements CacheDriver
{
    protected $ttl = 300;

    public function hasMultiple(array $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }

        return true;
    }

    public function getMultiple(array $keys, $default = null)
    {
        $values = [];

        foreach ($keys as $key) {
            $values[$key] = $this->get($key, $default);
        }

        return $values;
    }

    public function setMultiple(array $values, ?int $ttl = null)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return $this;
    }

    public function forever(string $key, $value)
    {
        return $this->set($key, $value, 0);
    }

    public function foreverMultiple(array $values)
    {
        return $this->setMultiple($values, 0);
    }

    public function deleteMultiple(array $keys)
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return $this;
    }

    public function remember(string $key, callable $callable, ?int $ttl = null)
    {
        if (!$this->has($key)) {
            $this->set($key, $value = call_user_func_array($callable, [$this]), $ttl);
        } else {
            $value = $this->get($key);
        }

        return $value;
    }

    public function increment(string $key, int $amount = 1, ?int $ttl = null)
    {
        $value = (int) $this->get($key);

        return $this->set($key, $value + $amount, $ttl);
    }

    public function decrement(string $key, int $amount = 1, ?int $ttl = null)
    {
        $value = (int) $this->get($key);

        return $this->set($key, $value - $amount, $ttl);
    }

    public function incrementFloat(string $key, float $amount = 1.0, ?int $ttl = null)
    {
        $value = (float) $this->get($key);

        return $this->set($key, $value + $amount, $ttl);
    }

    public function decrementFloat(string $key, float $amount = 1.0, ?int $ttl = null)
    {
        $value = (float) $this->get($key);

        return $this->set($key, $value - $amount, $ttl);
    }

    public function touch(string $key, ?int $ttl = null)
    {
        $this->set($key, $this->get($key), $ttl);
    }

    public function add(string $key, $value, ?int $ttl = null): bool
    {
        if ($this->has($key)) {
            return false;
        }

        $this->set($key, $value, $ttl);

        return true;
    }

    public function pull(string $key, $default = null)
    {
        $value = $this->get($key, $default);

        $this->delete($key);

        return $value;
    }

    protected function getTTL(?int $ttl = null)
    {
        if ($ttl === null) {
            $ttl = $this->ttl;
        }

        return $this->validateTTL($ttl);
    }

    protected function validateTTL(int $ttl)
    {
        if ($ttl < 0) {
            throw new \InvalidArgumentException('TTL could not be negative.');
        }

        return $ttl;
    }

    protected function isExpired(?int $time): bool
    {
        return $time === null or $time === '' or $time < 0 or ($time > 0 and $time <= time());
    }

    protected function getExpiresAt(?int $ttl = null)
    {
        $ttl = $this->getTTL($ttl);

        return $ttl > 0 ? time() + $ttl : 0;
    }

    protected function serialize($value)
    {
        if (is_scalar($value)) {
            if (ctype_digit((string) $value)) {
                return (int) $value;
            }

            if (is_numeric((string) $value)) {
                return (float) $value;
            }
        }

        return serialize($value);
    }

    protected function unserialize($value)
    {
        if (ctype_digit((string) $value)) {
            return (int) $value;
        }

        if (is_numeric((string) $value)) {
            return (float) $value;
        }

        return unserialize($value);
    }
}
