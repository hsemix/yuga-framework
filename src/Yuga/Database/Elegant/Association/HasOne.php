<?php

namespace Yuga\Database\Elegant\Association;

use Yuga\Database\Elegant\Model;
use Yuga\Database\Elegant\Builder;

class HasOne extends Association
{
    protected $child;
    protected \Yuga\Database\Elegant\Builder $query;
    
    public function __construct(Builder $query, protected \Yuga\Database\Elegant\Model $parent, protected $foreignKey, protected $otherKey)
    {
        $this->child = $query->getModel();
        $this->query = $query;
        parent::__construct($query, $this->parent);
    }
    
    public function addConditions()
    {
        $this->query->where($this->foreignKey, '=', $this->getParentIdValue())->limit(1);
    }

    public function getParentIdValue()
    {
        return $this->parent->getAttribute($this->otherKey);
    }

    public function save(Model $model)
    {
        $model->setAttribute($this->getPlainForeignKey(), $this->getParentIdValue());

        return $model->save() ? $model : false;
    }

    public function getPlainForeignKey()
    {
        $foreign = explode(".", (string) $this->foreignKey);
        return end($foreign);
    }
    
    public function saveMany($models)
    {
        foreach ($models as $model) {
            $this->save($model);
        }

        return $models;
    }

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults()
    {
        return $this->query->first();
    }
}