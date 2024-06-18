<?php

namespace Yuga\Cache;

class RedisCache extends CacheAbstract
{
    private $adapter;

    public function __construct(\Redis $adapter, int $ttl = 300)
    {
        $this->adapter = $adapter;

        $this->ttl = 300;

        return $this;
    }

    public function has(string $key): bool
    {
        return $this->adapter->exists($key);
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
        if ($ttl = $this->getTTL($ttl)) {
            $this->adapter->setex($key, $ttl, $this->serialize($value));
        } else {
            $this->adapter->set($key, $this->serialize($value));
        }

        return $this;
    }

    public function delete(string $key)
    {
        $this->adapter->del($key);

        return $this;
    }

    public function clear()
    {
        $keys = $this->adapter->keys('*');

        if ($prefix = $this->adapter->getOption(\Redis::OPT_PREFIX)) {
            $this->adapter->setOption(\Redis::OPT_PREFIX, '');

            $this->adapter->del($keys);

            $this->adapter->setOption(\Redis::OPT_PREFIX, $prefix);
        } else {
            $this->adapter->del($keys);
        }

        return $this;
    }

    public function increment(string $key, int $amount = 1, ?int $ttl = null)
    {
        $this->adapter->incrBy($key, $amount);

        $this->touch($key, $ttl);

        return $this;
    }

    public function decrement(string $key, int $amount = 1, ?int $ttl = null)
    {
        $this->adapter->decrBy($key, $amount);

        $this->touch($key, $ttl);

        return $this;
    }

    public function incrementFloat(string $key, float $amount = 1.0, ?int $ttl = null)
    {
        $this->adapter->incrByFloat($key, $amount);

        $this->touch($key, $ttl);

        return $this;
    }

    public function decrementFloat(string $key, float $amount = 1.0, ?int $ttl = null)
    {
        $this->adapter->incrByFloat($key, -$amount);

        $this->touch($key, $ttl);

        return $this;
    }

    public function touch(string $key, ?int $ttl = null)
    {
        if ($ttl = $this->getTTL($ttl)) {
            $this->adapter->expire($key, $ttl);
        } else {
            $this->adapter->persist($key);
        }

        return $this;
    }
}
