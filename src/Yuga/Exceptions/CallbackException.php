<?php
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

    protected $callback;

    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @param Request $request
     * @param \Exception $error
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