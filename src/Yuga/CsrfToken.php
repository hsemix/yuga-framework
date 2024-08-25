<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga;

use Yuga\Cookie\Cookie;

class CsrfToken
{
    const CSRF_KEY = 'CSRF-TOKEN';

    /**
     * Generate a random identifier for the CSRF token.
     *
     * @throws \RuntimeException
     * @return string
     */
    public static function generateToken(): string
    {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes(32));
        }

        $isSourceStrong = false;
        $random = bin2hex(openssl_random_pseudo_bytes(32, $isSourceStrong));

        if (!$isSourceStrong || !$random) {
            throw new \RuntimeException('IV generation failed');
        }

        return $random;
    }

    /**
     * Validate the CSRF token.
     *
     * @param string $token
     * @return bool
     */
    public function validate(?string $token): bool
    {
        $currentToken = $this->getToken();
        if ($token !== null && $currentToken !== null) {
            return function_exists('hash_equals') 
                ? hash_equals($token, $currentToken) 
                : $token === $currentToken;
        }

        return false;
    }

    /**
     * Set the CSRF token in a cookie.
     *
     * @param string $token
     * @return void
     */
    public function setToken(string $token): void
    {
        Cookie::put(static::CSRF_KEY, $token, 60 * 120);
    }

    /**
     * Get the CSRF token from the cookie.
     *
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->hasToken() ? Cookie::get(static::CSRF_KEY) : null;
    }

    /**
     * Check if the CSRF token is defined.
     *
     * @return bool
     */
    public function hasToken(): bool
    {
        return Cookie::exists(static::CSRF_KEY);
    }
}
