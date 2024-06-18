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
     * The JSON Web Token string
     *
     * @var string $token
     */
    private $token;

    /**
    * The secret used to create the JWT signature
    *
    * @var string $secret
    */
    private $secret;

    /**
     * JWT Constructor
     *
     * @param string $token
     * @param string $secret
     */
    public function __construct(string $token, ?string $secret = null)
    {
        $this->token = $token;
        if ($secret) {
            $this->secret = $secret;
        } else {
            $this->secret = 'Yuga Framework ' . Application::VERSION . ' ' . config('app.name', 'Yuga Framework');
        }
        
    }

    /**
     * Return the JSON Web Token String
     *
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Return the secret used to encode the JWT signature
     *
     * @return string
     */
    public function getSecret(): string
    {
        return $this->secret;
    }
}
