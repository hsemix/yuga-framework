<?php

namespace Yuga\Database\Migration\Schema;

abstract class Table
{
    /**
     * @param $name
     * @return Column
     */
    abstract public function column($name);
    
    /**
     * Create timestamp columns
     * @return static $this
     */
    abstract public function timestamps(): static;

    abstract public function renameColumn(string $fromName, string $toName);
}
