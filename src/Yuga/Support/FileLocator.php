<?php
/**
 * @author Mahad Tech Solutions
 */

namespace Yuga\Support;

use InvalidArgumentException;
use ReflectionClass;

class FileLocator
{
    private $namespaceMap = [];
    private $defaultNamespace = 'global';

    public function __construct()
    {
        $this->traverseClasses();
    }

    public function getNamespaceFromClass($class)
    {
        $reflection = new ReflectionClass($class);

        return $reflection->getNameSpaceName() === '' ? $this->defaultNamespace : $reflection->getNameSpaceName();
    }

    public function traverseClasses()
    {
        $classes = get_declared_classes();
        foreach ($classes as $class) {
            $namespace = $this->getNamespaceFromClass($class);
            $this->namespaceMap[$namespace][] = $class;
        }
    }

    public function getClassesOfNamespace($namespace)
    {
        if (!isset($this->namespaceMap[$namespace])) {
            throw new InvalidArgumentException('The Namespace '.$namespace.' doesnot exist');
        }

        return $this->namespaceMap[$namespace];
    }

    public function getNameSpaces()
    {
        return array_keys($this->namespaceMap);
    }
}
