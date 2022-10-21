<?php

namespace Yuga\Cache;

class FileCache extends CacheAbstract
{
    protected const EXTENSION = '.cache.txt';

    protected $path;

    protected $gcProbability;

    protected $gcDivisor;

    public function __construct(string $path, int $ttl = 300, int $gcProbability = 1, int $gcDivisor = 100)
    {
        $this->path = $path;

        $this->ttl = $this->validateTTL($ttl);

        $this->gcProbability = $gcProbability;

        $this->gcDivisor = $gcDivisor;

        $this->checkGarbageCollection();

        return $this;
    }

    public function has(string $key): bool
    {
        if ($this->isKeyExpired($key)) {
            $this->deleteKey($key);

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
        $file = $this->getFile($key);

        if (file_exists($file) and !is_readable($file)) {
            throw new CacheException('Cache item `'.$key.'` is not readable.');
        }

        if ($this->isFileExpired($file)) {
            $this->deleteFile($file);

            return $default;
        }

        $contents = file($file);

        return $contents ? $this->unserialize($contents[1] ?? '') : $default;
    }

    public function set(string $key, $value, ?int $ttl = null)
    {
        $this->validatePath();

        file_put_contents($this->getNewFile($key), $this->getExpiresAt($ttl).PHP_EOL.$this->serialize($value));

        return $this;
    }

    public function delete(string $key)
    {
        return $this->deleteKey($key);
    }

    public function clear()
    {
        $files = glob($this->path.DIRECTORY_SEPARATOR.'*'.self::EXTENSION);

        foreach ($files as $file) {
            unlink($file);
        }

        return $this;
    }

    public function collectGarbage()
    {
        $files = glob($this->path.DIRECTORY_SEPARATOR.'*'.self::EXTENSION);

        foreach ($files as $file) {
            $ttl = $this->getFileTTL($file);

            if ($this->isExpired($ttl)) {
                unlink($file);
            }
        }

        return $this;
    }

    protected function validatePath()
    {
        if (!file_exists($this->path)) {
            throw new CacheException('Cache path not exists.');
        }

        if (!is_readable($this->path)) {
            throw new CacheException('Cache path is not readable.');
        }

        if (!is_writable($this->path)) {
            throw new CacheException('Cache path is not writable.');
        }

        return $this;
    }

    protected function getFile(string $key): string
    {
        return $this->path.DIRECTORY_SEPARATOR.md5($key).self::EXTENSION;
    }

    protected function getNewFile(string $key)
    {
        $file = $this->getFile($key);

        if (file_exists($file)) {
            unlink($file);
        }

        return $file;
    }

    protected function getFileTTL($file): ?int
    {
        if (is_readable($file)) {
            $ttl = trim(fgets(fopen($file, 'r')));

            if (ctype_digit((string) $ttl)) {
                return (int) $ttl;
            }
        }

        return null;
    }

    protected function isFileExpired(string $file): bool
    {
        return $this->isExpired($this->getFileTTL($file));
    }

    protected function isKeyExpired(string $key): bool
    {
        return $this->isFileExpired($this->getFile($key));
    }

    protected function deleteFile(string $file)
    {
        if (file_exists($file)) {
            unlink($file);
        }

        return $this;
    }

    protected function deleteKey(string $key)
    {
        return $this->deleteFile($this->getFile($key));
    }

    protected function checkGarbageCollection()
    {
        if ($this->gcDivisor > 0 and mt_rand(1, $this->gcDivisor) <= $this->gcProbability) {
            $this->collectGarbage();
        }

        return $this;
    }
}
