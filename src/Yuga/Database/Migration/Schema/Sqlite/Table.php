<?php

namespace Yuga\Database\Migration\Schema\Sqlite;

use Yuga\Database\Migration\PDO;
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

    public function name($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Create timestamp columns
     * @return static $this
     */
    public function timestamps(): static
    {
        $this->column('updated_at')->datetime()->nullable();
        $this->column('created_at')->datetime();
        return $this;
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
        return (PDO::getInstance()->value(sprintf('pragma table_info (%s)', $this->name)) !== false);
    }

    /**
     * Create table
     */
    public function create()
    {
        if (!$this->exists()) {
            PDO::getInstance()->nonQuery($this->getQuery());
        }
    }

    public function getQuery($type = 'insert', array $options = [])
    {
        if (count($this->columns) > 0) {
            $query = [];
            /* @var $column Column */
            foreach ($this->columns as $column) {
                $query[] = $column->getQuery();
            }
            $sql = sprintf('create table %s (%s);', $this->name, implode(', ', $query));
            return $sql;
        }

        throw new \Exception('No Columns have been created yet');
    }

    public function alter()
    {
        if ($this->exists()) {

            /* @var $column Column */
            foreach ($this->columns as $column) {
                PDO::getInstance()->nonQuery(sprintf('ALTER TABLE %s CHANGE %s %s', $this->name, $column->getName(), $column->getQuery(false)));
                
                if ($column->getKeyRelationsQuery() !== '') {
                    PDO::getInstance()->nonQuery(sprintf('ALTER TABLE %s ADD %s', $this->name, $column->getKeyRelationsQuery()));
                }   
            }
        }
    }

    public function rename($name)
    {
        PDO::getInstance()->nonQuery(sprintf('alter table %s rename to %s', $this->name, $name));
        $this->name = $name;
    }

    public function dropIndex(array $indexes)
    {
        foreach ($indexes as $index) {
            PDO::getInstance()->nonQuery('ALTER TABLE ' . $this->name . ' DROP INDEX ' . $index . '');
        }
    }

    public function dropPrimary()
    {
        PDO::getInstance()->nonQuery('ALTER TABLE ' . $this->name . ' DROP PRIMARY KEY');
    }

    public function dropForeign(array $indexes)
    {
        foreach ($indexes as $index) {
            PDO::getInstance()->nonQuery('ALTER TABLE ' . $this->name . ' DROP FOREIGN KEY ' . $index . '');
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
        PDO::getInstance()->nonQuery('truncate table '. $this->name .';');
    }

    public function drop()
    {
        PDO::getInstance()->nonQuery(sprintf('drop table %s;', $this->name));
    }

    public function columnExists($column)
    {
        if ($this->exists()) {
            $result = PDO::getInstance()->doQuery(sprintf("pragma table_info (%s);", $this->name));
            $columns = $result->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($columns as $key) {
                if ($key['name'] == $column) {
                    return true;
                }
            }
            return false; 
        }

    }

    public function addColumns()
    {
        if ($this->exists()) {
            foreach ($this->columns as $column) {
                if(!$this->columnExists($column->getName())) {
                    PDO::getInstance()->nonQuery(sprintf('alter table %s add %s', $this->name, $column->getQuery()));
                }
            }
        }
    }

    public function rememberToken()
    {
        return $this->column('remember_token')->string(100)->nullable();
    }

    public function dropColumn($column)
    {
        $tableColumns = $this->getTableColumns();
        $originalName = $this->getName();
        $tempName = 'temp_'.$originalName;
        $query = 'BEGIN TRANSACTION;';

        $renameTable = $this->rename($tempName);

        $newTable = new static($tempName, true);
        $createNewTable = $newTable->copyTo($originalName, [$column]);
        $query .= $createNewTable->getQuery();

        $query .= 'insert into '. $originalName .' select '. implode(',', $createNewTable->getColumnNames()) .' from '.$tempName.';';
        $query .= 'drop table '.$tempName.';COMMIT;';

        PDO::getInstance()->executeSql($query);
        return $this;
    }


    public function getTableColumns()
    {
        if ($this->exists()) {
            $result = PDO::getInstance()->doQuery(sprintf("pragma table_info (%s);", $this->name));

            $columns = $result->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($columns as $column) {
                $tableColumn = $this->column($column['name']);
                $tableColumn->setType($column['type']);
                if (!$column['notnull']) {
                    $tableColumn->nullable();
                }
                if ($column['pk']) {
                    $tableColumn->primary();
                }
            }
        }
        return $this;
    }

    public function copyTo($table, array $excludeColums = [])
    {
        $newTable = new static($table);

        foreach ($this->columns as $column) {
            if (!in_array($column->getName(), $excludeColums)) {
                $newTable->columns[] = $column;
            }
        }

        return $newTable;
    }

    public function renameColumn($fromName, $toName)
    {
        if ($this->exists()) {
            if ($this->columnExists($fromName)) {
                PDO::getInstance()->nonQuery(sprintf('ALTER TABLE "%s" RENAME COLUMN "%s" TO %s', $this->name, $fromName, $toName));
            }
        }
    }
}
