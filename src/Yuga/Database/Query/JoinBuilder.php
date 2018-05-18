<?php
namespace Yuga\Database\Query;

class JoinBuilder extends Builder
{
    /**
     * @param string $key
     * @param string|mixed $operator
     * @param string|mixed $value
     *
     * @return static
     */
    public function on($column, $operator, $value)
    {
        return $this->joinHandler($column, $operator, $value, 'and');
    }

    /**
     * @param string $key
     * @param string|mixed $operator
     * @param string|mixed $value
     *
     * @return static
     */
    public function orOn($column, $operator, $value)
    {
        return $this->joinHandler($column, $operator, $value, 'or');
    }

    /**
     * @param string$key
     * @param string|mixed|null $operator
     * @param string|mixed|null $value
     * @param string $joiner
     *
     * @return static
     */
    protected function joinHandler($column, $operator = null, $value = null, $type = 'and')
    {
        $column = $this->addTablePrefix($column);
        $value = $this->addTablePrefix($value);
        $this->statements['criteria'][] = compact('column', 'operator', 'value', 'type');

        return $this;
    }
}
