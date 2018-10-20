<?php
namespace Yuga\Route\Shared;

use ReflectionClass;
use Yuga\Application\Application;

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

    protected function methodInjection($class, $method, $params)
    {
        $parameters = null;
        $app = Application::getInstance();
        $reflection = new ReflectionClass($class);
        if ($reflection->hasMethod($method)) {
            $reflectionMethod = $reflection->getMethod($method);
            $reflectionParameters = $reflectionMethod->getParameters();
            $dependecies = [];
            foreach ($reflectionParameters as $parameter) {
                if (!is_null($parameter->getClass())) {
                    $dependency = $parameter->getClass()->name;
                    if($binding = $this->isSingleton($app, $dependency)) {
                        $dependecies[] = $binding;
                    } else {
                        $dependecies[] = $app->resolve($dependency);
                    }
                }
            }
            foreach ($params as $paramKey => $paramVal) {
                $dependecies[$paramKey] = $paramVal;
            }
            $dependecies[] = $app;
            
        }
        return $dependecies;
    }
}
