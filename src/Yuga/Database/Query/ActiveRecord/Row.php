<?php

namespace Yuga\Database\Query\ActiveRecord;

use ArrayAccess;
use JsonSerializable;
use Yuga\Database\Query\Builder;
use Yuga\Support\Inflect;

class Row implements ArrayAccess, JsonSerializable
{
    private $attributes = [];

    /**
     * get a variable and make an object point to it.
     *
     * @param null
     *
     * @return void
     */
    public function __get($key)
    {
        return $this->attributes[$key];
    }

    /**
     * Set a variable and make an object point to it.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return !is_null($this->attributes[$key]);
    }

    /**
     * Unset an attribute on the model.
     *
     * @param string $key
     *
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key], $this->relations[$key]);
    }

    /**
     * Make the object act like an array when at access time.
     *
     * @param $offset
     * @param $value
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }

    /**
     * Determine whether an attribute exists on this model.
     *
     * @param $offset
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * Unset an attribute if it doesn't exist.
     *
     * @param $offset
     *
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    /**
     * Get the value of an attribute from an array given its key.
     *
     * @param string $offset
     *
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return isset($this->attributes[$offset]) ? $this->attributes[$offset] : null;
    }

    /**
     * Change the model to a json string.
     *
     * @param int|null $options
     *
     * @return string
     */
    public function toJson($options = null)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Change the model to a string.
     *
     * @param null
     *
     * @return void
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Implement a json serializer.
     *
     * @param null
     *
     * @return array
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        $attributes = (array) $this->attributes;
        $attributes = array_map(function ($attribute) {
            if (!is_array($attribute)) {
                if (!is_object($attribute)) {
                    $json_attribute = json_decode($attribute ?? '', true);
                    if (json_last_error() == JSON_ERROR_NONE) {
                        return $json_attribute;
                    }
                } else {
                    return (array) $attribute;
                }
            }

            return $attribute;
        }, $attributes);

        return $attributes;
    }

    /**
     * Change an object to an array.
     *
     * @param null
     *
     * @return mixed
     */
    public function toArray()
    {
        return $this->jsonSerialize();
    }

    public function refersTo(string $table, ?string $foreignKey = null, ?string $primaryKey = null)
    {
        if (is_null($primaryKey)) {
            $primaryKey = 'id';
        }

        if (is_null($foreignKey)) {
            $foreignKey = Inflect::singularize($table).'_id';
        }

        return $this->table($table)->where($primaryKey, $this->{$foreignKey})->first();
    }

    public function hasMany(string $table, ?string $foreignKey = null, ?string $primaryKey = null)
    {
        if (is_null($foreignKey)) {
            $foreignKey = Inflect::singularize($this->getActiveRecordTable()).'_id';
        }

        if (is_null($primaryKey)) {
            $primaryKey = 'id';
        }

        return $this->table($table)->where($foreignKey, $this->{$primaryKey})->get();
    }

    public function hasOne(string $table, ?string $foreignKey = null, ?string $primaryKey = null)
    {
        if (is_null($foreignKey)) {
            $foreignKey = Inflect::singularize($this->getActiveRecordTable()).'_id';
        }

        if (is_null($primaryKey)) {
            $primaryKey = 'id';
        }

        return $this->table($table)->where($foreignKey, $this->{$primaryKey})->first();
    }

    /**
     * Simulate the QueryBuilder and represent it as DB::anyMethod().
     */
    public static function __callStatic($method, $args)
    {
        return call_user_func_array([Builder::getInstance(), $method], $args);
    }

    /**
     * Simulate the QueryBuilder and represent it as (new DB)->anyMethod().
     */
    public function __call($method, $args)
    {
        return call_user_func_array([Builder::getInstance(), $method], $args);
    }
}
