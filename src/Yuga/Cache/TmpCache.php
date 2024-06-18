<?php

namespace Yuga\Cache;

class TmpCache extends CacheAbstract
{
    private $storage = [];

    public function __construct(int $ttl = 300)
    {
        $this->ttl = $this->validateTTL($ttl);

        return $this;
    }

    public function has(string $key): bool
    {
        if (!$file = $this->storage[$key] ?? null) {
            return false;
        }

        fseek($file, 0);

        $ttl = (int) trim(fgets($file));

        if ($this->isExpired($ttl)) {
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
        if (!$file = $this->storage[$key] ?? null) {
            return $default;
        }

        fseek($file, 0);

        $ttl = (int) trim(fgets($file));

        if ($this->isExpired($ttl)) {
            $this->delete($key);

            return $default;
        }

        $value = trim(fgets($file));

        return $this->unserialize($value);
    }

    public function set(string $key, $value, ?int $ttl = null)
    {
        $file = tmpfile();

        fwrite($file, $this->getExpiresAt($ttl) . PHP_EOL . $this->serialize($value));

        $this->storage[$key] = $file;

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
