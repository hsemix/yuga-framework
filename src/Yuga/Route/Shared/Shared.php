<?php
namespace Yuga\Route\Shared;

use ReflectionClass;
use Yuga\Database\Elegant\Model;
use Yuga\Application\Application;
use Yuga\Database\Elegant\Exceptions\ModelNotFoundException;

/**
 * Share some methods to Route classes
 */
trait Shared
{
    protected function isSingleton(Application $app, $class)
    {
        foreach(array_values($app->getSingletons()) as $instance){
            if(get_class($instance) == $class){
                return $instance;
            }
        }
        return false;
    }

    protected function methodInjection($class, $method, $params, $request = null)
    {
        $parameters = null;
        $app = Application::getInstance();
        $reflection = new ReflectionClass($class);
        if ($reflection->hasMethod($method)) {
            $reflectionMethod = $reflection->getMethod($method);
            $reflectionParameters = $reflectionMethod->getParameters();
            $dependecies = [];
            foreach ($reflectionParameters as $parameter) {
                $name = $parameter->getType() && !$parameter->getType()->isBuiltin() ? $parameter->getType()->getName() : null;
                
                if (!is_null($name)) {
                    $dependency = $name;
                    if($binding = $this->isSingleton($app, $dependency)) {
                        $dependecies[] = $binding;
                    } else {
                        $dependecies[] = $app->resolve($dependency);
                    }
                }
            } 
            foreach ($params as $paramVal) {
                $dependecies[] = $paramVal;
            }
            $dependecies[] = $app;
            
        }
        return $dependecies;
    }
}
