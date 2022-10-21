<?php
/**
 * All models are collected in this collection class for further manipulation.
 */

namespace Yuga\Database\Elegant;

use ArrayAccess;
use Closure;
use Countable;
use Iterator;
use JsonSerializable;
use Yuga\Pagination\Pagination;
use Yuga\Route\Exceptions\NotFoundHttpException;
use Yuga\Support\Arr;

class Collection implements ArrayAccess, Iterator, JsonSerializable, Countable
{
    protected $query;
    protected $items = [];
    protected $pagination;
    protected static $instances = [];

    public function __construct($items = [], $query = null)
    {
        $this->items = $items;
        $this->query = $query;
    }

    public function __toString()
    {
        return $this->toJson();
    }

    public function addItem($item)
    {
        $this->items = $item;
    }

    public function getItems()
    {
        return $this->items;
    }

    public function first()
    {
        return $this->items[0];
    }

    public function last()
    {
        return $this->items[count($this->items) - 1];
    }

    /**
     * Find a model in the collection by key.
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @return \Yuga\Database\Elegant\Model
     */
    public function find($key, $default = null)
    {
        if ($key instanceof Model) {
            $key = $key->getPrimaryKey();
        }

        return new static(Arr::first($this->items, function ($itemKey) use ($key, $default) {
            if ($itemKey instanceof Model) {
                if ($default == null) {
                    return $itemKey->{$itemKey->getPrimaryKey()} == $key;
                }

                return $itemKey->{$key} == $default;
            }

            if ($default == null) {
                return $itemKey->id == $key;
            }

            return $itemKey->{$key} == $default;
        }));
    }

    /**
     * Make the object act like an array when at access time.
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        reset($this->items);
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        $var = current($this->items);

        return $var;
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        $var = key($this->items);

        return $var;
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        $var = next($this->items);

        return $var;
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        $key = key($this->items);
        $var = ($key !== null && $key !== false);

        return $var;
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->items);
    }

    /**
     * Run a filter over each of the items.
     *
     * @param callable|null $callback
     *
     * @return static
     */
    public function filter(callable $callback = null)
    {
        if ($callback) {
            $return = [];

            foreach ($this->items as $key => $value) {
                if ($callback($value, $key)) {
                    $return[$key] = $value;
                }
            }

            return new static($return);
        }

        return new static(array_filter($this->items));
    }

    /**
     * Filter items by the given key value pair.
     *
     * @param string $key
     * @param mixed  $value
     * @param bool   $strict
     *
     * @return static
     */
    public function where($key, $value, $strict = true)
    {
        return $this->filter(function ($item) use ($key, $value, $strict) {
            return $strict ? data_get($item, $key) === $value : data_get($item, $key) == $value;
        });
    }

    /**
     * Get all of the items in the collection.
     *
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * Get an item from the collection by key.
     *
     * @param mixed|null $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function get($key = null, $default = null)
    {
        if ($key) {
            if ($this->offsetExists($key)) {
                return $this->items[$key];
            }

            return value($default);
        }

        return $this->items;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->items;
    }

    public function toJson()
    {
        return json_encode($this->jsonSerialize());
    }

    public function toArray()
    {
        return json_decode($this->toJson(), true);
    }

    public function flatten()
    {
        return $this->arrayFlatten($this->toArray(), 0);
    }

    public function arrayFlatten($array, $preserve_keys = 1, $newArray = [])
    {
        foreach ($array as $key => $child) {
            if (is_array($child)) {
                $newArray = $this->arrayFlatten($child, $preserve_keys, $newArray);
            } elseif ($preserve_keys + is_string($key) > 1) {
                $newArray[$key] = $child;
            } else {
                $newArray[] = $child;
            }
        }

        return $newArray;
    }

    public function chunk($count = 2, $callback = null)
    {
        $items = array_map(function ($item) {
            return new static($item);
        }, array_chunk($this->items, $count));

        if ($callback) {
            return $callback(new static($items));
        }

        return new static($items);
    }

    public function map(Closure $callback)
    {
        array_map($callback, $this->items);

        return $this;
    }

    public function each(Closure $callback)
    {
        return $this->map($callback);
    }

    /**
     * Fetch a nested element of the collection.
     *
     * @param string $key
     *
     * @return Collection
     */
    public function fetch($key)
    {
        return new static(array_fetch($this->items, $key));
    }

    public function pagination(array $options = null)
    {
        return count($this->items) > 0 ? ((is_object($this->items[0]->getPagination())) ? $this->items[0]->getPagination()->render($options) : null) : null;
    }

    /**
     * Slice the underlying collection array.
     *
     * @param int $offset
     * @param int $length
     *
     * @return static
     */
    public function slice($offset, $length = null)
    {
        return new static(array_slice($this->items, $offset, $length, true));
    }

    /**
     * Sort through each item with a callback.
     *
     * @param Closure $callback
     *
     * @return Collection
     */
    public function sort(Closure $callback)
    {
        uasort($this->items, $callback);

        return $this;
    }

    /**
     * Get a value retrieving callback.
     *
     * @param string $value
     *
     * @return \Closure
     */
    protected function valueRetriever($value)
    {
        return function ($item) use ($value) {
            return data_get($item, $value);
        };
    }

    /**
     * Sort the collection using the given Closure.
     *
     * @param \Closure|string $callback
     * @param int             $options
     * @param bool            $descending
     *
     * @return Collection
     */
    public function sortBy($callback, $options = SORT_REGULAR, $descending = false)
    {
        $results = [];

        if (is_string($callback)) {
            $callback = $this->valueRetriever($callback);
        }

        // First we will loop through the items and get the comparator from a callback
        // function which we were given. Then, we will sort the returned values and
        // and grab the corresponding values for the sorted keys from this array.
        foreach ($this->items as $key => $value) {
            $results[$key] = $callback($value);
        }

        $descending ? arsort($results, $options) : asort($results, $options);

        // Once we have sorted all of the keys in the array, we will loop through them
        // and grab the corresponding model so we can set the underlying items list
        // to the sorted version. Then we'll just return the collection instance.
        foreach (array_keys($results) as $key) {
            $results[$key] = $this->items[$key];
        }

        $this->items = $results;

        return $this;
    }

    /**
     * Sort the collection in descending order using the given Closure.
     *
     * @param \Closure|string $callback
     * @param int             $options
     *
     * @return Collection
     */
    public function sortByDesc($callback, $options = SORT_REGULAR)
    {
        return $this->sortBy($callback, $options, true);
    }

    /**
     * Splice portion of the underlying collection array.
     *
     * @param int   $offset
     * @param int   $length
     * @param mixed $replacement
     *
     * @return Collection
     */
    public function splice($offset, $length = 0, $replacement = [])
    {
        return new static(array_splice($this->items, $offset, $length, $replacement));
    }

    /**
     * Get and remove the last item from the collection.
     *
     * @return mixed|null
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Push an item onto the beginning of the collection.
     *
     * @param mixed $value
     *
     * @return void
     */
    public function prepend($value)
    {
        array_unshift($this->items, $value);
    }

    /**
     * Push an item onto the end of the collection.
     *
     * @param mixed $value
     *
     * @return void
     */
    public function push($value)
    {
        $this->items[] = $value;
    }

    /**
     * Pulls an item from the collection.
     *
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function pull($key, $default = null)
    {
        return array_pull($this->items, $key, $default);
    }

    /**
     * Put an item in the collection by key.
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return void
     */
    public function put($key, $value)
    {
        $this->items[$key] = $value;
    }

    /**
     * Reduce the collection to a single value.
     *
     * @param callable $callback
     * @param mixed    $initial
     *
     * @return mixed
     */
    public function reduce($callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Get one or more items randomly from the collection.
     *
     * @param int $amount
     *
     * @return mixed
     */
    public function random($amount = 1)
    {
        $keys = array_rand($this->items, $amount);

        return is_array($keys) ? array_intersect_key($this->items, array_flip($keys)) : $this->items[$keys];
    }

    /**
     * Reverse items order.
     *
     * @return \Nova\Support\Collection
     */
    public function reverse()
    {
        return new static(array_reverse($this->items));
    }

    /**
     * Get and remove the first item from the collection.
     *
     * @return mixed|null
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    public function paginate($limit)
    {
        $page = 1;
        if ($this->query) {
            $this->pagination = $pagination = new Pagination($page, $limit, $this->query->count());

            return $this->query->limit($limit)->offset($pagination->offset())->get();
        } else {
            throw new NotFoundHttpException('Pagination only works with models');
        }
    }

    public function pages(array $options = null)
    {
        return $this->pagination($options);
    }

    public function links()
    {
        return $this->pages();
    }

    public function orderBy($key, $asc = 'ASC')
    {
        $order = strtolower($asc);
        if ($this->count() > 0) {
            if ($order == 'asc') {
                $order = false;
            } elseif ($order == 'desc') {
                $order = true;
            }

            $this->sksort($this->items, $key, $order);
        }

        return $this;
    }

    protected function sksort($array, $subkey = 'id', $sort_ascending = false)
    {
        $temp_array = [];
        if (count($array)) {
            $temp_array[key($array)] = array_shift($array);
        }
        foreach ($array as $key => $val) {
            $offset = 0;
            $found = false;
            foreach ($temp_array as $tmp_key => $tmp_val) {
                if (!$found && strtolower($val[$subkey]) > strtolower($tmp_val[$subkey])) {
                    $temp_array = array_merge((array) array_slice($temp_array, 0, $offset), [$key => $val], array_slice($temp_array, $offset));
                    $found = true;
                }
                $offset++;
            }
            if (!$found) {
                $temp_array = array_merge($temp_array, [$key => $val]);
            }
        }

        if ($sort_ascending) {
            $array = array_reverse($temp_array);
        } else {
            $array = $temp_array;
        }
    }

    /**
     * Pluck an array.
     */
    public function pluck($property)
    {
        $out = [];
        for ($i = 0, $length = count($this->items); $i < $length; $i++) {
            $out[] = $this->items[$i][$property];
        }

        return new static($out);
    }

    public function getRecordsFilteredCount()
    {
        return 0;
    }

    public function getTotalRecordsCount()
    {
        return 0;
    }

    /**
     * Take the first or last {$limit} items.
     *
     * @param int $limit
     *
     * @return Collection
     */
    public function take($limit = null)
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }

        return $this->slice(0, $limit);
    }

    /**
     * Return only unique items from the collection array.
     *
     * @return Collection
     */
    public function unique()
    {
        return new static(array_unique($this->items));
    }

    /**
     * Get the keys of the collection items.
     *
     * @return static
     */
    public function keys()
    {
        return new static(array_keys($this->items));
    }

    /**
     * Reset the keys on the underlying array.
     *
     * @return Collection
     */
    public function values()
    {
        $this->items = array_values($this->items);

        return $this;
    }
}
