<?php
namespace Yuga\Route\Annotation;

use Yuga\Route\Route as Router;
use Tests\Controllers\TestAnnotations;
use Yuga\Route\Router\RouteController;

/**
 * @Annotation
 * @Target("METHOD")
 */
class Route
{
    protected $route = null;
    protected $name = null;
    protected $method = 'get';
    public function __construct($values)
    {
        $this->route = $values['value'];
        if (isset($values['name'])) {
            $this->name = $values['name'];
        }
        if (isset($values['method'])) {
            $this->method = $values['method'];
        }

        $this->bootRouter();
    }

    protected function bootRouter()
    {
        $router = new RouteController($this->route, TestAnnotations::class);

        return $router;
    }
}