<?php

namespace Yuga\Authenticate\Shared;

use Yuga\JWTAuth\Jwt;
use Yuga\JWTAuth\UsesJWTTokens;

trait UseApiToken
{
    use UsesJWTTokens;

    public function generateToken($secrete = null)
    {
        $token = $this->createToken([
            'id' => $this->id
        ], $secrete);
        return new Jwt($token, $secrete);
    }

    public function access($token = null, $secrete = null)
    {
        $data = $this->verifyToken($token, $secrete);

        return $this->find($data->id);
    }
}