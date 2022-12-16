<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Support;

use Countable;
use ArrayAccess;
use Traversable;
use JsonSerializable;
use IteratorAggregate;

class Config implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    protected $data;
    protected $default = null;
    
    public function load($file, $key = null)
    {
        $file = str_replace(".", "/", $file);
        $file = require path($file.'.php');

        if ($key)
            $this->data[$key] = $file;
        else
            $this->data = $file;
        
        return $this;
    }

    public function get($key, $default = null)
    {
        if (empty($key)) return $this;

        $this->default = $default;
        $segments = explode('.', $key);
        $data = $this->data;
        foreach ($segments as $segment) {
            if (isset($data[$segment])) {
                $data = $data[$segment];
            } else {
                $data = $this->default;
                break;
            }
        }

        return $data;
    }

    public function exists($key)
    {
        return $this->get($key) !== $this->default;
    }

    public function getAll()
    {
        return $this->data;
    }

    public function set($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $keyItem => $value) {
                $this->set($keyItem, $value);
            }

            return $this;
        }

        $data = &$this->data;
        
        foreach (explode('.', $key) as $segment) {

            if (!isset($data[$segment]) || !is_array($data[$segment])) {
                $data[$segment] = [];
            }

            $data = &$data[$segment];
        }

        $data = $value;

        return $this;
    }

    /**
     * Delete the given key or keys
     * @param array<TKey>|array<TKey, TValue>|int|string $keys
     * @return Config
     */
    public function delete($keys)
    {
       $keys = (array) $keys;

        foreach ($keys as $key) {
            if ($this->exists($key)) {
               unset($this->data[$key]);

               continue;
            }

           $items = &$this->data;
           $segments = explode('.', $key);
           $lastSegment = array_pop($segments);

            foreach ($segments as $segment) {
                if (!isset($items[$segment]) || !is_array($items[$segment])) {
                   continue 2;
                }

                $items = &$items[$segment];
            }

           unset($items[$lastSegment]);
        }

       return $this;
    }

    /**
     * Return the value of a given key or all the values as JSON
     *
     * @param  mixed  $key
     * @param  int  $options
     * @return string|false
     */
    public function toJson($key = null, $options = 0)
    {
        if (is_string($key)) {
            return json_encode($this->get($key), $options);
        }

        $options = $key === null ? 0 : $key;

        return json_encode($this->data, $options);
    }

    /**
     * Check if a given key exists
     *
     * @param  int|string  $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    /**
     * Return the value of a given key
     *
     * @param  int|string  $key
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Set a given value to the given key
     *
     * @param int|string|null  $key
     * @param mixed  $value
     */
    public function offsetSet($key, $value): void
    {
        if ($key === null) {
            $this->data[] = $value;

            return;
        }

        $this->set($key, $value);
    }

    /**
     * Delete the given key
     *
     * @param  int|string  $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        $this->delete($key);
    }

    /**
     * Return the number of items in a given key
     *
     * @param  int|string|null  $key
     * @return int
     */
    public function count($key = null): int
    {
        return count($this->get($key));
    }

    /**
     * Get an iterator for the stored items
     *
     * @return \ArrayIterator<TKey, TValue>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }

    /*
     * --------------------------------------------------------------
     * JsonSerializable interface
     * --------------------------------------------------------------
     */

    /**
     * Return items for JSON serialization
     *
     * @return array<TKey, TValue>
     */
    public function jsonSerialize(): array
    {
        return $this->data;
    }
}