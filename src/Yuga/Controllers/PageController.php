<?php
namespace Yuga\Controllers;

use Yuga\Http\Request;

class PageController extends Controller
{
    public function notFound(Request $request)
    {
        include_once 'not-found.php';
    }
}