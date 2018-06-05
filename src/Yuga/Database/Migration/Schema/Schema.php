<?php
namespace Yuga\Database\Migration\Schema;

class Schema
{
    public $sqlTable;

    public function __construct()
    {
        $this->sqlTable = '\Yuga\Database\Migration\Schema\\'.ucfirst(env('DATABASE_DRIVER', 'mysql')).'\\Table';
    }
    public function create($name, $callback)
    {
        $sqlTable = $this->sqlTable;
        $table = new $sqlTable($name);
        $callback($table);
        $table->create();
    }

    public function drop($name)
    {
        $sqlTable = $this->sqlTable;
        $table = new $sqlTable($name);
        $table->drop();
    }

    public function modify($name, $callback)
    {
        $sqlTable = $this->sqlTable;
        $table = new $sqlTable($name);
        $callback($table);
        $table->alter();
    }

    public function dropIfExists($name)
    {
        $sqlTable = $this->sqlTable;
        $table = new $sqlTable($name);
        $table->dropIfExists();
    }

}