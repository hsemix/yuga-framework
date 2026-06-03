<?php

namespace Yuga\Http\Psr7;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Yuga\Http\Redirect;

class YugaResponseFactory
{

    public static function fromMixed(mixed $value): ResponseInterface
    {
        if ($value instanceof ResponseInterface) {
            return $value;
        }

        if ($value instanceof Redirect) {
            $headers = $value->getHeaders();

            if (!isset($headers['Location'])) {
                $headers['Location'] = $value->getPath() ?: '/';
            }

            return new Response(
                $value->getStatusCode(),
                $headers,
                ''
            );
        }

        if (is_string($value)) {
            return new Response(
                200,
                ['Content-Type' => 'text/html; charset=UTF-8'],
                $value
            );
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return new Response(
                200,
                ['Content-Type' => 'text/html; charset=UTF-8'],
                (string) $value
            );
        }

        if (is_array($value)) {
            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );
        }

        if (is_object($value)) {
            return new Response(
                200,
                ['Content-Type' => 'application/json'],
                json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );
        }

        return new Response(
            200,
            ['Content-Type' => 'text/html; charset=UTF-8'],
            (string) $value
        );
    }
}
