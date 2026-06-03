<?php

declare(strict_types=1);

namespace Yuga\Http\Psr7;

use Psr\Http\Message\ResponseInterface;

class Psr7Emitter
{
    public function emit(ResponseInterface $response): void
    {
        if (!headers_sent()) {
            http_response_code($response->getStatusCode());

            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header($name . ': ' . $value, false);
                }
            }
        }

        echo (string) $response->getBody();
    }
}
