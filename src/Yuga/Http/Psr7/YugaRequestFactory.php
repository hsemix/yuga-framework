<?php

namespace Yuga\Http\Psr7;

use Psr\Http\Message\ServerRequestInterface;
use Yuga\Http\Request;

class YugaRequestFactory
{
    public static function fromPsr7(ServerRequestInterface $request): Request
    {
        $GLOBALS['_GET'] = $request->getQueryParams();
        $GLOBALS['_POST'] = is_array($request->getParsedBody()) ? $request->getParsedBody() : [];
        $GLOBALS['_COOKIE'] = $request->getCookieParams();
        $GLOBALS['_SERVER'] = array_merge($GLOBALS['_SERVER'] ?? [], $request->getServerParams());

        $GLOBALS['_SERVER']['REQUEST_METHOD'] = $request->getMethod();
        $GLOBALS['_SERVER']['REQUEST_URI'] = $request->getUri()->getPath();

        if ($request->getUri()->getQuery() !== '' && $request->getUri()->getQuery() !== '0') {
            $GLOBALS['_SERVER']['REQUEST_URI'] .= '?' . $request->getUri()->getQuery();
        }

        if ($request->getUri()->getHost() !== '' && $request->getUri()->getHost() !== '0') {
            $GLOBALS['_SERVER']['HTTP_HOST'] = $request->getUri()->getHost();
        }

        foreach ($request->getHeaders() as $name => $values) {
            $normalized = strtolower((string) $name);

            if ($normalized === 'host') {
                continue;
            }

            $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
            $_SERVER[$key] = implode(', ', array_unique($values));
        }

        $_SERVER['HTTP_HOST'] = $request->getUri()->getHost();

        if ($request->getUri()->getPort()) {
            $_SERVER['HTTP_HOST'] .= ':' . $request->getUri()->getPort();
        }

        return new Request();
    }
}