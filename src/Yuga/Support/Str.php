<?php

/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Support;

class Str
{

    public static function getFirstOrDefault($value, $default = null)
    {
        return ($value !== null && trim($value) !== '') ? trim($value) : $default;
    }

    public static function isUtf8($str)
    {
        return ($str === mb_convert_encoding(mb_convert_encoding($str, 'UTF-32', 'UTF-8'), 'UTF-8', 'UTF-32'));
    }

    public static function substr($text, $maxLength, $end = '...', $encoding = 'UTF-8')
    {
        if (strlen($text) > $maxLength) {
            return mb_substr($text, 0, $maxLength, $encoding) . $end;
        }

        return $text;
    }

    public static function wordWrap($text, $limit)
    {
        $words = explode(' ', $text);

        return join(' ', array_splice($words, 0, $limit));
    }

    public static function base64Encode($obj)
    {
        return base64_encode(serialize($obj));
    }

    public static function base64Decode($str, $defaultValue = null)
    {
        $req = base64_decode($str);
        if ($req !== false) {
            $req = unserialize($req);
            if ($req !== false) {
                return $req;
            }
        }

        return $defaultValue;
    }

    public static function deCamelize($word)
    {
        return preg_replace_callback('/(^|[a-z])([A-Z])/',
            function ($matches) {
                return strtolower(strlen($matches[1]) ? $matches[1] . '_' . $matches[2] : $matches[2]);
            },
            $word
        );
    }

    public static function camelize($word)
    {
        $word = preg_replace_callback('/(^|_)([a-z])/', function ($matches) {
            return strtoupper($matches[2]);
        }, strtolower($word));
        $word[0] = strtolower($word[0]);

        return $word;
    }

    /**
     * Returns weather the $value is a valid email.
     * @param string $email
     * @return bool
     */
    public static function isEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    public static function length($value)
	{
		return strlen($value);
    }
    

    public static function slug($key, $separator = '-')
	{

		// Remove all characters that are not the separator, letters, numbers, or whitespace.
		$key = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', static::lower($key));

		// Replace all separator characters and whitespace by a single separator
		$key = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $key);

		return trim($key, $separator);
    }
    
    public static function title($value)
	{
		if (MB_STRING)
		{
			return mb_convert_case($value, MB_CASE_TITLE, static::encoding());
		}

		return ucwords(strtolower($value));
    }
    
    public static function lower($value)
	{
        // return (MB_STRING) ? mb_strtolower($value, static::encoding()) : strtolower($value);
        return strtolower($value);
    }
    
    public static function upper($value)
	{
		return (MB_STRING) ? mb_strtoupper($value, static::encoding()) : strtoupper($value);
    }

    /**
     * Determine if a given string contains a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    public static function contains($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if a given string starts with a given substring.
     *
     * @param  string  $haystack
     * @param  string|array  $needles
     * @return bool
     */
    public static function startsWith($haystack, $needles)
    {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && substr($haystack, 0, strlen($needle)) === (string) $needle) {
                return true;
            }
        }

        return false;
    }

}