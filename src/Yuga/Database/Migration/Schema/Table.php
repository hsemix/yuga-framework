<?php
namespace Yuga\Database\Migration\Schema;

abstract class Table
{
    /**
     * @param $name
     * @return Column
     */
    abstract public function column($name);
}