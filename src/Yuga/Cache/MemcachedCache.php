<?php

namespace Yuga\Cache;

class MemcachedCache extends CacheAbstract
{
    private $adapter;

    public function __construct(\Memcached $adapter, int $ttl = 300)
    {
        $this->adapter = $adapter;

        $this->ttl = 300;

        return $this;
    }

    public function has(string $key): bool
    {
        return $this->adapter->get($key) !== false;
    }

    public function get(string $key, $default = null)
    {
        if ($contents = $this->adapter->get($key)) {
            return $this->unserialize($contents);
        }

        return null;
    }

    public function set(string $key, $value, ?int $ttl = null)
    {
        $ttl = $this->getTTL($ttl);

        $this->adapter->set($key, $this->serialize($value), $ttl ? time() + $ttl : 0);

        return $this;
    }

    public function put(string $key, $value, ?int $ttl = null)
    {
        return $this->set($key, $value, $ttl);
    }

    public function delete(string $key)
    {
        $this->adapter->delete($key);

        return $this;
    }

    public function clear()
    {
        $this->adapter->flush();

        return $this;
    }

    public function increment(string $key, int $amount = 1, ?int $ttl = null)
    {
        $this->adapter->increment($key, $amount);

        return $this;
    }

    public function decrement(string $key, int $amount = 1, ?int $ttl = null)
    {
        $this->adapter->decrement($key, $amount);

        return $this;
    }

    public function touch($key, ?int $ttl = null)
    {
        $ttl = $this->getTTL($ttl);

        $this->adapter->touch($key, $ttl ? time() + $ttl : 0);

        return $this;
    }
}
