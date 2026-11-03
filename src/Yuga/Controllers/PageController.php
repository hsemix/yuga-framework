<?php

namespace Yuga\Controllers;

use Yuga\Http\Request;

class PageController extends Controller
{
    public function notFoundOld(Request $request)
    {
        if (env('NOT_FOUND_404_FILE')) {
            return view(env('NOT_FOUND_404_FILE'))->withRequest($request);
        } else {
            include_once __DIR__ . '/not-found.php';
        }
    }

    public function formExpired(Request $request)
    {
        if (env('FORM_EXPIRED_FILE')) {
            return view(env('FORM_EXPIRED_FILE'))->withRequest($request);
        } else {
            include_once __DIR__ . '/form-expired.php';
        }
    }

    public function notFound(Request $request)
    {

        if ($request->isAjax()) {
            return new \Nyholm\Psr7\Response(
                404,
                ['Content-Type' => 'application/json; charset=utf-8'],
                json_encode(
                    ['error' => 'Not Found'],
                    JSON_THROW_ON_ERROR
                )
            );
        }
        
        if (env('NOT_FOUND_404_FILE')) {
            $content = view(env('NOT_FOUND_404_FILE'))
                ->withRequest($request);

            return \Yuga\Http\Psr7\YugaResponseFactory::fromMixed($content)
                ->withStatus(404);
        }

        ob_start();

        try {
            require __DIR__ . '/not-found.php';

            $content = ob_get_clean();
        } catch (\Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        return \Yuga\Http\Psr7\YugaResponseFactory::fromMixed($content)
            ->withStatus(404);
    }
}
