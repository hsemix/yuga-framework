<?php
namespace Yuga\Database\Elegant\Association;

use Yuga\Database\Elegant\Model;
use Yuga\Database\Elegant\Builder;
use Yuga\Database\Elegant\Collection;

class Mergeable extends BelongsTo
{
    protected $models;
    protected $mergeType;
   
    public function __construct(Builder $query, Model $parent, $foreignKey, $otherKey, $type, $relation)
    {
        $this->mergeType = $type;
        parent::__construct($query, $parent, $foreignKey, $otherKey, $relation); 
    }
    
}