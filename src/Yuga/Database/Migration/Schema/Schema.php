<?php
namespace Yuga\Database\Migration\Schema;
class Schema
{
    public function create($name, $callback)
    {
        $table = new Table($name);
        $callback($table);
        $table->create();
    }

    public function drop($name)
    {
        $table = new Table($name);
        $table->drop();
    }

    public function modify($name, $callback)
    {
        $table = new Table($name);
        $callback($table);
        $table->alter();
    }

    public function dropIfExists($name)
    {
        $table = new Table($name);
        $table->dropIfExists();
    }

}