<?php

namespace Yuga\Database\Elegant\Association;

use Yuga\Database\Elegant\Builder;
use Yuga\Database\Elegant\Model;

class BelongsTo extends Association
{
    protected $child;
    protected $otherKey;
    protected $foreignKey;

    public function __construct(Builder $query, Model $parent, $foreignKey, $otherKey, $child)
    {
        $this->otherKey = $otherKey;
        $this->child = $child;
        $this->foreignKey = $foreignKey;
        $this->parent = $parent;
        $this->query = $query;
        parent::__construct($query, $parent);
    }

    public function addConditions()
    {
        $table = $this->child->getTable();
        $this->query->where($table.'.'.$this->otherKey, '=', $this->parent->{$this->foreignKey});
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
