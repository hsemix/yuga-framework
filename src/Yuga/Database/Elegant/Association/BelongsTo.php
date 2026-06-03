<?php

namespace Yuga\Database\Elegant\Association;

use Yuga\Database\Elegant\Model;
use Yuga\Database\Elegant\Builder;
use Yuga\Database\Elegant\Collection;

class BelongsTo extends Association
{

    public function __construct(Builder $query, Model $parent, protected $foreignKey, protected $otherKey, protected $child)
    {
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