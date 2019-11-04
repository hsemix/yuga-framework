<?php

namespace Yuga\Support;

use ArrayAccess;
use InvalidArgumentException;
use Yuga\Database\Elegant\Collection;

class Arr
{
    /**
     * Determine whether the given value is array accessible.
     *
     * @param  mixed  $value
     * @return bool
     */
    public static function accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * Pluck an array of values from an array.
     *
     * @param  array  $array
     * @param  string|array  $value
     * @param  string|array|null  $key
     * @return array
     */
    public static function pluck($array, $value, $key = null)
    {
        $results = [];

        [$value, $key] = static::explodePluckParameters($value, $key);

        foreach ($array as $item) {
            $itemValue = data_get($item, $value);

            if (is_null($key)) {
                $results[] = $itemValue;
            } else {
                $itemKey = data_get($item, $key);

                if (is_object($itemKey) && method_exists($itemKey, '__toString')) {
                    $itemKey = (string) $itemKey;
                }

                $results[$itemKey] = $itemValue;
            }
        }

        return $results;
    }

    /**
     * Explode the "value" and "key" arguments passed to "pluck".
     *
     * @param  string|array  $value
     * @param  string|array|null  $key
     * @return array
     */
    protected static function explodePluckParameters($value, $key)
    {
        $value = is_string($value) ? explode('.', $value) : $value;

        $key = is_null($key) || is_array($key) ? $key : explode('.', $key);

        return [$value, $key];
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|int  $key
     * @return bool
     */
    public static function exists($array, $key)
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    /**
     * Collapse an array of arrays into a single array.
     *
     * @param  array  $array
     * @return array
     */
    public static function collapse($array)
    {
        $results = [];

        foreach ($array as $values) {
            if ($values instanceof Collection) {
                $values = $values->all();
            } elseif (! is_array($values)) {
                continue;
            }

            $results[] = $values;
        }

        return array_merge([], ...$results);
    }
}