<?php

namespace Yuga\Database\Elegant\Association;

use Yuga\Database\Elegant\Model;
use Yuga\Database\Elegant\Builder;
use Yuga\Database\Elegant\Collection;

class MergeableMany extends HasMany
{
    public $related;
    public $mergeClass;

    public function __construct(Builder $query, Model $parent, public $mergeType, $id, $localKey)
    {
        $this->query = $query;
        $this->mergeClass = class_base($parent->getMergeableClass());
        $this->foreignKey = $id;
        $this->parent = $parent;
        
        parent::__construct($query, $parent, $id, $localKey);
    }

    #[\Override]
    public function addConditions()
    {     
        $this->query->where($this->mergeType, strtolower((string) $this->mergeClass))->where($this->foreignKey, '=', $this->getParentIdValue());
    }

    #[\Override]
    public function save(Model $model)
    {
        $mergeClassBase = class_base($this->mergeClass);
        $model->setAttribute($this->getPlainMergeableType(), strtolower((string) $mergeClassBase));
        $model->setAttribute($this->getPlainMergeId(), $this->parent->{$this->parent->getPrimaryKey()});
        
        return parent::save($model);
    }

    public function firstOrCreate(array $attributes)
    {
        if (is_null($instance = $this->where($attributes)->first())) {
            $instance = $this->create($attributes);
        }

        return $instance;
    }

    public function create(array $attributes)
    {
        $instance = $this->related->newInstance($attributes);
        $this->setForeignAttributesForCreate($instance);
        $instance->save();
        return $instance;
    }

    protected function setForeignAttributesForCreate(Model $model)
    {
        $model->{$this->getPlainForeignKey()} = $this->getParentKey();
        $model->{$this->last(explode('.', (string) $this->mergeType))} = $this->mergeClass;
    }

    public function getPlainMergeId()
    {
        return $this->last(explode('.', (string) $this->foreignKey));
    }

    public function getPlainMergeableType()
    {
        return $this->last(explode('.', (string) $this->mergeType));
    }

    public function last(array $colllection)
    {
        return end($colllection);
    }
    
    #[\Override]
    public function saveMany($models)
    {
        foreach ($models as $model) {
            $this->save($model);
        }

        return $models;
    }
}