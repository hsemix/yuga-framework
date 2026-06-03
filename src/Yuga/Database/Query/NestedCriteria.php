<?php

declare(strict_types=1);

namespace Yuga\Database\Query;

class NestedCriteria extends Builder
{
    /**
     * @param string $column
     * @param string|mixed|null $operator
     * @param string|mixed|null $value
     * @param string $joiner
     * @return static
     */
    #[\Override]
    protected function whereHandler($column, $operator = null, $value = null, $type = 'AND')
    {
        $this->addTablePrefix($column);
        $this->statements['criteria'][] = ['column' => $column, 'operator' => $operator, 'value' => $value, 'type' => $type];

        return $this;
    }
}
