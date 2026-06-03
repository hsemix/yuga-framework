<?php

declare(strict_types=1);

namespace Yuga\JWTAuth;

use Yuga\Application\Application;

/**
 * Value object for the generated JSON Web Token, takes the token and
 * the secret.
 */
class Jwt
{
    /**
     * The secret used to create the JWT signature
     */
    private ?string $secret;

    /**
     * JWT Constructor
     *
     * @param string $secret
     */
    public function __construct(/**
     * The JSON Web Token string
     */
    private readonly string $token, ?string $secret = null)
    {
        if ($secret) {
            $this->secret = $secret;
        } else {
            $this->secret = 'Yuga Framework ' . Application::VERSION . ' ' . config('app.name', 'Yuga Framework');
        }
        
    }

    /**
     * Return the JSON Web Token String
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Return the secret used to encode the JWT signature
     */
    public function getSecret(): string
    {
        return $this->secret;
    }
}
