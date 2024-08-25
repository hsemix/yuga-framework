<?php

namespace Yuga\Cookie;

class Cookie
{
    /**
     * Check if a cookie exists.
     *
     * @param string $name
     * @return bool
     */
    public static function exists($name): bool
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Get the value of a cookie.
     *
     * @param string $name
     * @return string|null
     */
    public static function get($name): ?string
    {
        return $_COOKIE[$name] ?? null;
    }

    /**
     * Set a cookie with the given parameters.
     *
     * @param string $name
     * @param string $value
     * @param int|null $expiry
     * @param string|null $domain
     * @param bool $secure
     * @param string $path
     * @return bool
     */
    public static function put($name, $value, $expiry = null, $domain = null, $secure = false, $path = '/'): bool
    {
        return self::create($name, $value, $expiry, $domain, $secure, $path);
    }

    /**
     * Create a cookie with the given parameters.
     *
     * @param string $name
     * @param string $value
     * @param int|null $expiry
     * @param string|null $domain
     * @param bool $secure
     * @param string $path
     * @return bool
     */
    public static function create($name, $value, $expiry = null, $domain = null, $secure = true, $path = '/'): bool
    {
        if (empty($name)) {
            return false;
        }

        $host = request()->getHost();
        $domain = $domain ?? (count(explode('.', $host)) > 2 ? $host : $host);
        $expiryTime = $expiry !== null ? time() + $expiry : time() + 60 * 60 * 24 * 6004;

        return setcookie($name, $value, $expiryTime, $path, $domain, $secure);
    }

    /**
     * Delete a cookie.
     *
     * @param string $name
     * @return bool
     */
    public static function delete($name): bool
    {
        return self::put($name, '', -1);
    }
}
