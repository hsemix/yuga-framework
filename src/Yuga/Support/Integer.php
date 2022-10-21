<?php
/**
 * @author Mahad Tech Solutions
 */

namespace Yuga\Support;

class Integer
{
    /**
     * Check if a given value is a counting type or if the value of the string has numbers in it.
     *
     * @param string $str
     *
     * @return bool
     */
    public static function isInteger($str)
    {
        return filter_var($str, FILTER_VALIDATE_INT) !== false;
    }

    public static function isNummeric($val)
    {
        return self::isInteger($val) || is_numeric($val);
    }
}
