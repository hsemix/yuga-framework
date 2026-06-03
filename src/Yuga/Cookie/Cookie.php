<?php

namespace Yuga\Cookie;

use DateTimeInterface;
use Psr\Http\Message\ResponseInterface;

class Cookie
{
    protected static array $queued = [];

    public static function get(string $name, mixed $default = null): mixed
    {
        return $_COOKIE[$name] ?? $default;
    }

    public static function has(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    public static function exists(string $name): bool
    {
        return self::has($name);
    }

    public static function put(
        string $name,
        string $value,
        int|DateTimeInterface|null $expiry = null,
        string $path = '/',
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): void {
        static::$queued[] = static::make(
            $name,
            $value,
            $expiry,
            $path,
            $domain,
            $secure,
            $httpOnly,
            $sameSite
        );
    }

    public static function forever(
        string $name,
        string $value,
        string $path = '/',
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): void {
        static::put(
            $name,
            $value,
            time() + 60 * 60 * 24 * 365 * 5,
            $path,
            $domain,
            $secure,
            $httpOnly,
            $sameSite
        );
    }

    public static function delete(
        string $name,
        string $path = '/',
        ?string $domain = null
    ): void {
        static::put(
            $name,
            '',
            time() - 3600,
            $path,
            $domain
        );

        unset($_COOKIE[$name]);
    }

    public static function queued(): array
    {
        return static::$queued;
    }

    public static function flushQueued(): void
    {
        static::$queued = [];
    }

    public static function attachToResponse(ResponseInterface $response): ResponseInterface
    {
        foreach (static::$queued as $cookie) {
            $response = $response->withAddedHeader('Set-Cookie', $cookie);
        }

        static::flushQueued();

        return $response;
    }

    protected static function make(
        string $name,
        string $value,
        int|DateTimeInterface|null $expiry = null,
        string $path = '/',
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = true,
        string $sameSite = 'Lax'
    ): string {
        $expires = static::normalizeExpiry($expiry);

        $cookie = rawurlencode($name) . '=' . rawurlencode($value);

        if ($expires !== null) {
            $cookie .= '; Expires=' . gmdate('D, d M Y H:i:s T', $expires);
            $cookie .= '; Max-Age=' . max(0, $expires - time());
        }

        $cookie .= '; Path=' . $path;

        if ($domain) {
            $cookie .= '; Domain=' . $domain;
        }

        if ($secure) {
            $cookie .= '; Secure';
        }

        if ($httpOnly) {
            $cookie .= '; HttpOnly';
        }

        if ($sameSite !== '' && $sameSite !== '0') {
            $cookie .= '; SameSite=' . ucfirst(strtolower($sameSite));
        }

        return $cookie;
    }

    protected static function normalizeExpiry(int|DateTimeInterface|null $expiry): ?int
    {
        if ($expiry instanceof DateTimeInterface) {
            return $expiry->getTimestamp();
        }

        if ($expiry === null) {
            return null;
        }

        return $expiry > time() ? $expiry : time() + $expiry;
    }
}