<?php
namespace Yuga\Container;

use Closure;
use ArrayAccess;
use ReflectionClass;
use Yuga\Container\Support\ClassNotInstantiableException;

class Container implements ArrayAccess
{
    protected $bindings = [];

    protected $instances = [];

    private static $thisInstances 		= [];

    public function bind($key, $value, $singleton = false)
    {
        $key = ltrim($key, '\\');
        if ($value instanceof Closure) {
            if ($singleton) {
                $this->instances[$key] = $value($this);
            }
            $this->bindings[$key] = $value($this);
        } else {
            $this->bindings[$key] = compact('value', 'singleton');
        }
        
    }

    public function has($key)
    {
        if (array_key_exists($key, $this->instances) || array_key_exists($key, $this->bindings)) {
            return true;
        }
        return false;
    }

    public function singleton($key, $value)
    {
        return $this->bind($key, $value, true);
    }
    public function getBinding($key)
    {
        $key = ltrim($key, '\\');
        if (!array_key_exists($key, $this->bindings)){
            return null;
        } else if(array_key_exists($key, $this->instances)) {
            return $this->instances[$key];
        } else if($this->bindings[$key] instanceof Closure) {
            return $this->bindings[$key];
        } else {
            return $this->bindings[$key]['value'];
        }
    }

    public function getBindings()
    {
        return array_keys($this->bindings);
    }

    public function make($key)
    {
        return $this->getBinding($key);
    }

    public function get($key)
    {

        if(array_key_exists($key, $this->instances)) {
            return $this->getBinding($key);
        } else if($this->bindings[$key] instanceof Closure) {        
            return $this->getBinding($key);
        } else if(is_string($this->bindings[$key]) && strpos($this->bindings[$key], '/') !== false) {
            return $this->getBinding($key);
        } else if(is_object($this->bindings[$key])) {
            return $this->make($key);
        } else {
            return $this->resolve($key);
        }
        
    }

    public function getSingletons()
    {
        return $this->instances;
    }

    protected function isSingleton($key)
    {
        $binding = $this->getBinding($key);
        if ($binding === null)
            return false;
        
        return true;//$binding['singleton'];// === true;
    }

    protected function singletonResolved($key)
    {
        return array_key_exists($key, $this->instances);
    }

    protected function getSingletonInstance($key)
    {
        return $this->singletonResolved($key) ? $this->instances[$key] : null;
    }

    protected function prepareObject($key, $object)
    {
        if ($this->isSingleton($key)) {
            $this->instances[$key] = $object;
        }

        return $object;
    }

    public function resolve($key, array $arguments = [])
    {
        
        $class = $this->getBinding($key);
        if ($class === null) {
            $class = $key;
        }

        if ($this->isSingleton($key) && $this->singletonResolved($key)) {
            return $this->getSingletonInstance($key);
        }
        
        $object = $this->buildObject($class, $arguments);
        return $this->prepareObject($key, $object);
    }

    public function inSingletons($class)
    {
        foreach(array_values($this->getSingletons()) as $instance){
            if(get_class($instance) == $class){
                return $instance;
            }
        }
        return false;
    }

    protected function buildDependencies($arguments, $dependencies, $className)
    {
        foreach ($dependencies as $dependency) {
            if ($dependency->isOptional()) continue;
            if ($dependency->isArray()) continue;

            $class = $dependency->getClass();
            if ($class === null) continue;

            if (get_class($this) === $class->name) {
                $arguments[] = $this;
                continue;
            }
            $arguments[] = $this->resolve($class->name);
        }

        return $arguments;
    }


    protected function buildObject($class, array $arguments = [])
    {
        
        $className = isset($class['value']) ? $class['value'] : $class;
        
        $reflector = new ReflectionClass($className);
        
        if (!$reflector->isInstantiable()) {
            throw new ClassNotInstantiableException("Class {$className} cannot be Instantiated");
        }

        if ($reflector->getConstructor() !== null) {
            $constructor = $reflector->getConstructor();
            $dependencies = $constructor->getParameters();

            $arguments = $this->buildDependencies($arguments, $dependencies, $class); 
            $object = $reflector->newInstanceArgs($arguments);           
        } else {
            $object = new $reflector->name;
        }
        
        return $object;
    }

    public function offsetGet($key)
    {
        return $this->make($key);
    }

    public function offsetSet($key, $value)
    {
        $this->bind($key, $value);
    }

    public function offsetExists($key)
    {
        return array_key_exists($key, $this->bindings);
    }

    public function offsetUnset($key)
    {
        unset($this->bindings[$key]);
    }

}