<?php

namespace Yuga\Cache;

class ArrayCache extends CacheAbstract
{
    private $storage = [];

    public function __construct(int $ttl = 300)
    {
        $this->ttl = $this->validateTTL($ttl);

        return $this;
    }

    public function has(string $key): bool
    {
        if (!$item = $this->storage[$key] ?? null) {
            return false;
        }

        if ($this->isExpired($item['ExpireAt'])) {
            $this->delete($key);

            return false;
        }

        return true;
    }

    public function hasMultiple(array $keys): bool
    {
        $has = true;

        foreach ($keys as $key) {
            if (!$this->has($key)) {
                $has = false;
            }
        }

        return $has;
    }

    public function get(string $key, $default = null)
    {
        if (!$item = $this->storage[$key] ?? null) {
            return false;
        }

        if ($this->isExpired($item['ExpireAt'])) {
            $this->delete($key);

            return $default;
        }

        return $item['Value'];
    }

    public function set(string $key, $value, ?int $ttl = null)
    {
        $this->storage[$key] = [
            'ExpireAt' => $this->getExpiresAt($ttl),
            'Value'    => $value,
        ];

        return $this;
    }

    public function delete(string $key)
    {
        unset($this->storage[$key]);

        return $this;
    }

    public function clear()
    {
        $this->storage = [];

        return $this;
    }
}
