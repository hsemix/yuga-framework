<?php

namespace Yuga\JWTAuth;

use DateTime;
use Yuga\JWTAuth\Token;

trait UsesJWTTokens
{
    /**
     * Create a JWT Token to be used for authorization
     */
    public function createToken($payload, $secret = null)
    {
        return Token::create($this->generateDefaults($payload), $secret);
    }

    /**
     * Verify a JWT Token to be used for authorization
     */
    public function verifyToken($token, $secret = null)
    {
        return Token::decode($token, $secret);
    }

    /**
     * Generate some defaults for the payload
     */
    public function generateDefaults(array $payload = []): array
    {
        $issuedAt = new DateTime('now');
        $expiryAt = (new DateTime('now'))->modify('+3 month');
        $host = request()->getHost();
        if (empty($payload)) {
            return [
                'iat' => $issuedAt->getTimestamp(),
                'iss' => $host,
                'exp' => $expiryAt->getTimestamp(),
            ];
        } else {
            if (!isset($payload['iat'])) {
                $payload['iat'] = $issuedAt->getTimestamp();
            }

            if (!isset($payload['exp'])) {
                $payload['exp'] = $expiryAt->getTimestamp();
            }

            if (!isset($payload['iss'])) {
                $payload['iss'] = $expiryAt->getTimestamp();
            }

            return $payload;
        }
        
    }
}