<?php
namespace Yuga\Controllers;
use Yuga\Widgets\NotFound;
class PageController extends Controller
{
    public function notFound()
    {
        echo new NotFound;
    }
}