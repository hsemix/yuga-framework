<?php
/**
* All models are collected in this collection class for further manipulation
*/
namespace Yuga\Database\Elegant;

use Closure;
use Iterator;
use Countable;
use ArrayAccess;
use JsonSerializable;
use Yuga\Support\Arr;
use Yuga\Support\Inflect;
use Yuga\Pagination\Pagination;

class Collection  implements ArrayAccess, Iterator, JsonSerializable, Countable
{
    protected $items = [];
    protected static $instances = [];
    protected $pagination;
    protected $query;
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
        return $this->items;
    }

    public function last()
    {
        return $this->items[count($this->items) - 1];
    }

    /**
     * Find a model in the collection by key.
     *
     * @param  mixed  $key
     * @param  mixed  $default
     * @return \Yuga\Database\Elegant\Model
     */
    public function find($key, $default = null)
    {
        if ($key instanceof Model) {
            $key = $key->getPrimaryKey();
        }

        return Arr::first($this->items, function ($itemKey, $model) use ($key) {
            return $model->getPrimaryKey() == $key;
        }, $default);
    }

    /**
	* Make the object act like an array when at access time
	*
	*/
    public function offsetSet($offset, $value) 
    {
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetExists($offset) 
    {
        return isset($this->items[$offset]);
    }

    public function offsetUnset($offset) 
    {
        unset($this->items[$offset]);
    }

    public function offsetGet($offset) 
    {
        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }
    
    public function rewind()
    {
        reset($this->items);
    }
  
    public function current()
    {
        $var = current($this->items);
        return $var;
    }
  
    public function key() 
    {
        $var = key($this->items);
        return $var;
    }
  
    public function next() 
    {
        $var = next($this->items);
        return $var;
    }
  
    public function valid()
    {
        $key = key($this->items);
        $var = ($key !== NULL && $key !== FALSE);
        return $var;
    }

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Run a filter over each of the items.
     *
     * @param  callable|null  $callback
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
     * @param  string  $key
     * @param  mixed  $value
     * @param  bool  $strict
     * @return static
     */
    public function where($key, $value, $strict = true)
    {
        return $this->filter(function ($item) use ($key, $value, $strict) {
            return $strict ? data_get($item, $key) === $value
                           : data_get($item, $key) == $value;
        });
    }

    /**
     * Get all of the items in the collection.
     *
     * @return array
     */
    public function all(){
        return $this->items;
    }

    /**
     * Get an item from the collection by key.
     *
     * @param  mixed  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if ($this->offsetExists($key)) {
            return $this->items[$key];
        }

        return value($default);
    }

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

    public function arrayFlatten($array, $preserve_keys = 1, &$newArray = []) 
    {
        foreach ($array as $key => $child) {
            if(is_array($child)) {
                $newArray =& $this->arrayFlatten($child, $preserve_keys, $newArray);
            } elseif ($preserve_keys + is_string($key) > 1) {
                $newArray[$key] = $child;
            } else {
                $newArray[] = $child;
            }
        }
        return $newArray;
    }

    public function chunk($count = 2)
    {
        $chunk = $count;
        
        $items = array_chunk($this->items, $chunk);

        return new static($items);
    }

    public function map(Closure $callback)
    {
        return array_map($callback, $this->items);
    }

    public function pagination(array $options = null)
    {
        return count($this->items) > 0 ? (is_object($this->items[0]->getPagination())) ? $this->items[0]->getPagination()->render($options) : null : null;
    }


    public function paginate($limit)
    {
        $page = 1;
        $this->pagination = $pagination = new Pagination($page, $limit, $this->query->count());
        return $this->query->limit($limit)->offset($pagination->offset())->get();
    }

    public function pages(array $options = null)
    {
        return $this->pagination($options);
    }

}