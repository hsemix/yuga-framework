<?php
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
    protected function whereHandler($column, $operator = null, $value = null, $type = 'AND')
    {
        $key = $this->addTablePrefix($column);
        $this->statements['criteria'][] = compact('column', 'operator', 'value', 'type');

        return $this;
    }
}
