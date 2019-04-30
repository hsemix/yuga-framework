<?php
namespace Yuga\Cookie;

class Cookie
{
    public static function exists($name)
    {
        return (isset($_COOKIE[$name])) ? true : false;
    }

    public static function get($name)
    {
        return $_COOKIE[$name];
    }

    public static function put($name, $value, $expiry = null, $domain = null, $secure = null, $path = '/')
    {
        $expiry = ($expiry === null) ? time() + 60 * 60 * 24 * 6004 : time() + $expiry;
        return setcookie($name, $value, (($expiry > 0) ? $expiry : null), $path, $domain, $secure);
    }

    public static function create($name, $value, $expiry = null, $domain = null, $secure = null, $path = '/')
    {
        if ($domain === null) {
            $sub = explode('.', request()->getHost());
            $domain = (count($sub) > 2) ? request()->getHost() : '.' . request()->getHost();
        }
        $expiry = ($expiry === null) ? time() + 60 * 60 * 24 * 6004 : time() + $expiry;
        return setcookie($name, $value, (($expiry > 0) ? $expiry : null), $path, $domain, $secure);
    }
    
    public static function delete($name)
    {
        return self::put($name, '', - 1);
    }
}