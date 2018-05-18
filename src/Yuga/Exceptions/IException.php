<?php
namespace Yuga\Exceptions;
use Exception;
use Yuga\Http\Request;

interface IException
{
    /**
     * @param Request $request
     * @param \Exception $error
     * @return Request|null
     */
    public function handleError(Request $request, Exception $error);

}