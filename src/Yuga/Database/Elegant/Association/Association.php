<?php
namespace Yuga\Database\Elegant\Association;

use Closure;
use Yuga\Database\Elegant\Model;
use Yuga\Database\Elegant\Builder;
use Yuga\Interfaces\Database\Elegant\Association\Association as Relation;

abstract class Association implements Relation
{
    private $query;
    private $parent;
    private $child;
    static $conditions;
    public function __construct(Builder $query, Model $parent)
    {
        $this->query = $query;
        $this->parent = $parent;
        $this->child = $query->getModel();
        
        $this->addConditions();
    }

    abstract public function addConditions();
    /**
    *   Redirect all unknown methods to the query Builder, it could be aware of them
    */

    public function __call($method, $parameters)
    {
        $result = call_user_func_array([$this->query, $method], $parameters);

        if ($result === $this->query) {
            return $this;
        }

        return $result;
    }
    public function noConditions(Closure $callback)
    {
        return call_user_func($callback);
    }

    public function getLazy()
    {
        return $this->get();
    }

    protected function getKeys(array $models, $key = null)
    {
        return array_unique(array_values(array_map(function ($value) use ($key) {
            return $key ? $value->getAttribute($key) : $value->getPrimaryKey();
        }, $models)));
    }
    
}