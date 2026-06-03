<?php

declare(strict_types=1);

namespace Yuga\Exceptions;

use Exception;
use Yuga\Http\Request;

abstract class RouteExceptionHandler implements IException
{
    abstract public function handleError(Request $request, Exception $error);
}
