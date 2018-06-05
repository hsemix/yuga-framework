<?php
namespace Yuga\Route\Exceptions;

use Exception;
use Yuga\Http\Request;
use Yuga\Route\Router\RouteUrl;
use Yuga\Exceptions\RouteExceptionHandler;
use Yuga\Route\Exceptions\NotFoundHttpException;

class NotFoundHttpExceptionHandler extends RouteExceptionHandler
{
    /**
     * @param Request $request
     * @param \Exception $error
     * @throws \Exception
     */
    public function handleError(Request $request, Exception $error)
    {
        // Return json errors if we encounter an error on the API.
        if ($request->isAjax() !== false) {
            response()->json(['error' => $error->getMessage()]);
        }
        if ($error instanceof NotFoundHttpException && $error->getCode() == 404) {
            $request->setRewriteCallback('Yuga\Controllers\PageController@notFound');
            return $request;
        }
        throw $error;
    }
}