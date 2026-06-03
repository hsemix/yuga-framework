<?php
namespace Yuga\Controllers;

use Yuga\Http\Request;

class PageController extends Controller
{
    public function notFound(Request $request)
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
}