<?php

declare(strict_types=1);

namespace Yuga\Database\Elegant\Association;

use Yuga\Database\Elegant\Model;
use Yuga\Database\Elegant\Builder;
use Yuga\Database\Elegant\Collection;

class Mergeable extends BelongsTo
{
    protected $models;
   
    public function __construct(Builder $query, Model $parent, $foreignKey, $otherKey, protected $mergeType, $relation)
    {
        parent::__construct($query, $parent, $foreignKey, $otherKey, $relation); 
    }
    
}
