<?php

declare(strict_types=1);

namespace Yuga\Exceptions;

use Closure;
use Exception;
use Yuga\Http\Request;

/**
 * Class CallbackException
 *
 *
 *
 * @package Yuga\Exceptions
 */
class CallbackException implements IException
{

    public function __construct(protected \Closure $callback)
    {
    }

    /**
     * @return Request|null
     */
    public function handleError(Request $request, Exception $error)
    {
        /* Fire exceptions */
        return call_user_func($this->callback,
            $request,
            $error
        );
    }
}
