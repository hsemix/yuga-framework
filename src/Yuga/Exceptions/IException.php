<?php

declare(strict_types=1);

namespace Yuga\Exceptions;

use Exception;
use Yuga\Http\Request;

interface IException
{
    /**
     * @return Request|null
     */
    public function handleError(Request $request, Exception $error);

}
