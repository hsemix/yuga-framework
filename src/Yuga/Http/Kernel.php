<?php

declare(strict_types=1);

namespace Yuga\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface Kernel
{
    public function handle(ServerRequestInterface $request): ResponseInterface;

    public function terminate(ServerRequestInterface $request, ResponseInterface $response): void;
}
