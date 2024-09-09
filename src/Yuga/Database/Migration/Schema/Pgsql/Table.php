<?php

namespace Yuga\Database\Migration\Schema\Pgsql;

use Yuga\Database\Migration\PDO;
use Yuga\Database\Migration\Schema\Pgsql\Column;
use Yuga\Database\Migration\Schema\Table as SqlTable;

class Table extends SqlTable
{
    protected $name;
    protected $columns = [];
    
    public function __construct($name = null, $getTableSchema = false)
    {
        $this->name = $name;

        if ($getTableSchema) {
            $this->getTableColumns();
        }
    }
    /**
     * @param $name
     * @return Column
     */
    public function column($name)
    {
        $column = new Column($this->name);
        $column->setName($name);

        $this->columns[] = $column;

        return $column;
    }

    public function getPrimary($default = null)
    {
        if (count($this->columns) > 0) {
            /* @var $column Column */
            foreach ($this->columns as $column) {
                if ($column->getIndex() == Column::INDEX_PRIMARY) {
                    return $column;
                }
            }
        }

        return $default;
    }

    public function getColumnByIndex($index)
    {
        return $this->columns[$index];
    }

    /**
     * Create timestamp columns
     * @return static $this
     */
    public function timestamps(): static
    {
        $this->column('updated_at')->datetime()->nullable()->index();
        $this->column('created_at')->datetime()->index();
        return $this;
    }

    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getColumnNames($lower = false, $excludePrimary = false)
    {
        $names = [];
        /* @var $column Column */
        foreach ($this->columns as $column) {
            if ($excludePrimary && $column->getIndex() === Column::INDEX_PRIMARY) {
                continue;
            }
            if ($lower) {
                $names[] = strtolower($column->getName());
            } else {
                $names[] = $column->getName();
            }
        }

        return $names;
    }

    public function getColumn($name, $strict = false)
    {
        /* @var $column Column */
        foreach ($this->columns as $column) {
            if (($strict === true && $column->getName() === $name) || ($strict === false && strtolower($column->getName()) === strtolower($name))) {
                return $column;
            }
        }

        return null;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function exists()
    {
        return (PDO::getInstance()->value('SELECT EXISTS (SELECT 1 FROM   information_schema.tables WHERE  table_schema = ? AND table_name = ?);', [env('DATABASE_SCHEMA', 'public'), $this->name]) !== false);
    }

    /**
     * Create table
     */
    public function create()
    {
        if (!$this->exists()) {
            $query = [];

            /* @var $column Column */
            foreach ($this->columns as $column) {
                $query[] = $column->getQuery();
            }

            $sql = sprintf('CREATE TABLE "%s" (%s)', $this->name, implode(', ', $query));
            PDO::getInstance()->nonQuery($sql);
        }
    }

    public function alter()
    {
        if ($this->exists() && count($this->columns) > 0) {

            /* @var $column Column */
            foreach ($this->columns as $column) {
                $sql = sprintf('ALTER TABLE "%s" ALTER COLUMN "%s" TYPE %s', $this->name, $column->getName(), str_replace('"' . $column->getName() . '"', '', $column->getQuery(false)));

                //NULL and DEFAULT cannot be set while changing column type
                $sql = preg_replace('/ not null/', '', $sql);
                $sql = preg_replace('/ null/', '', $sql);
                //If it is set, DEFAULT is the last definition
                $sql = preg_replace('/DEFAULT .*/', '', $sql);
                PDO::getInstance()->nonQuery($sql);
                if ($column->getKeyRelationsQuery() !== '') {
                    PDO::getInstance()->nonQuery(sprintf('ALTER TABLE "%s" ADD %s', $this->name, $column->getKeyRelationsQuery()));
                }   
            }
        }
    }

    public function columnExists($column)
    {
        if ($this->exists()) {
            $result = PDO::getInstance()->doQuery(sprintf("SELECT column_name FROM information_schema.columns WHERE table_name='%s' and column_name='%s'", $this->name, $column));
            return ($result->rowCount() == 1) ? true : false;   
        }

    }

    public function rename($name)
    {
        PDO::getInstance()->nonQuery('ALTER TABLE "' . $this->name . '" RENAME TO "' . $name . '";');
        $this->name = $name;
    }

    public function addColumns()
    {
        if ($this->exists() && count($this->columns) > 0) {
            foreach ($this->columns as $column) {
                if (!$this->columnExists($column->getName())) {
                    PDO::getInstance()->nonQuery(sprintf('ALTER TABLE "%s" ADD %s', $this->name, $column->getQuery()));
                }
            }
        }
    }

    public function rememberToken()
    {
        return $this->column('remember_token')->string(100)->nullable();
    }

    public function dropIndex(array $indexes)
    {
        foreach ($indexes as $index) {
            PDO::getInstance()->nonQuery('ALTER TABLE "' . $this->name . '" DROP INDEX "' . $index . '"');
        }
    }

    public function dropPrimary()
    {
        PDO::getInstance()->nonQuery('ALTER TABLE "' . $this->name . '" DROP PRIMARY KEY');
    }

    public function dropForeign(array $indexes)
    {
        foreach ($indexes as $index) {
            PDO::getInstance()->nonQuery('ALTER TABLE "' . $this->name . '" DROP FOREIGN KEY "' . $index . '"');
        }
    }

    public function dropIfExists()
    {
        if ($this->exists()) {
            $this->drop();
        }
    }

    public function truncate()
    {
        PDO::getInstance()->nonQuery('TRUNCATE TABLE "'. $this->name .'";');
    }

    public function drop()
    {
        PDO::getInstance()->nonQuery('DROP TABLE "' . $this->name . '";');
    }

    public function getTableColumns()
    {
        if ($this->exists()) {
            $result = PDO::getInstance()->doQuery(sprintf('SELECT * FROM information_schema.columns WHERE "table_schema"=\'%s\' AND "table_name"=\'%s\'', env('DATABASE_SCHEMA', 'public'), $this->name));

            $columns = $result->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($columns as $column) {
                $tableColumn = $this->column($column['column_name']);
                $tableColumn->setType($column['udt_name']);
                if ($column['is_nullable'] != 'NO') {
                    $tableColumn->nullable();
                }
                // if ($column['pk']) {
                //     $tableColumn->primary();
                // }
            }
        }
        return $this;
    }

    public function renameColumn(string $fromName, string $toName)
    {
        if ($this->exists()) {
            if ($this->columnExists($fromName)) {
                PDO::getInstance()->nonQuery(sprintf('ALTER TABLE "%s" RENAME COLUMN "%s" TO %s', $this->name, $fromName, $toName));
            }
        }
    }
}
