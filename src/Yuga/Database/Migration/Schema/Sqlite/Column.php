<?php

namespace Yuga\Database\Migration\Schema\Sqlite;

use Yuga\Database\Migration\PDO;
use Yuga\Database\Migration\PdoHelper;

class Column
{
    protected $table;
    protected $name;
    protected $type;
    protected $length;
    protected $defaultValue;
    protected $encoding;
    protected $attributes;
    protected $nullable;
    protected $index;
    protected $increment;
    protected $comment;
    protected $relationTable;
    protected $relationColumn;
    protected $relationUpdateType;
    protected $relationDeleteType;

    const INDEX_PRIMARY = 'PRIMARY KEY';
    const INDEX_UNIQUE = 'UNIQUE';
    const INDEX_INDEX = 'INDEX';

    const TYPE_VARCHAR = 'varchar';
    const TYPE_LONGTEXT = 'text';
    const TYPE_TEXT = 'text';
    const TYPE_MEDIUMTEXT = 'text';
    const TYPE_TINYTEXT = 'text';
    const TYPE_INT = 'integer';
    const TYPE_TINYINT = 'boolean';
    const TYPE_MEDIUMINT = 'integer';
    const TYPE_BIGINT = 'integer';
    const TYPE_DECIMAL = 'decimal';
    const TYPE_FLOAT = 'float';
    const TYPE_DOUBLE = 'float';
    const TYPE_REAL = 'int';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'datetime';
    const TYPE_TIMESTAMP = 'datetime';
    const TYPE_TIME = 'time';
    const TYPE_CHAR = 'char';
    const TYPE_BINARY = 'blob';
    const TYPE_TINYBLOB = 'blob';
    const TYPE_MEDIUMBLOB = 'blob';
    const TYPE_BLOB = 'blob';
    const TYPE_LONGBLOB = 'blob';
    const TYPE_ENUM = 'enum';
    const TYPE_GEOMETRY = 'text';
    const TYPE_POINT = 'float';
    const TYPE_LINESTRING = 'varchar';
    const TYPE_POLYGON = 'text';

    public static $INDEXES = [
        self::INDEX_PRIMARY,
        self::INDEX_UNIQUE,
        self::INDEX_INDEX,
    ];

    public static $TYPES = [
        self::TYPE_VARCHAR,
        self::TYPE_LONGTEXT,
        self::TYPE_TEXT,
        self::TYPE_MEDIUMTEXT,
        self::TYPE_TINYTEXT,
        self::TYPE_INT,
        self::TYPE_TINYINT,
        self::TYPE_MEDIUMINT,
        self::TYPE_BIGINT,
        self::TYPE_DECIMAL,
        self::TYPE_FLOAT,
        self::TYPE_DOUBLE,
        self::TYPE_REAL,
        self::TYPE_BOOLEAN,
        self::TYPE_DATE,
        self::TYPE_DATETIME,
        self::TYPE_TIMESTAMP,
        self::TYPE_TIME,
        self::TYPE_CHAR,
        self::TYPE_BINARY,
        self::TYPE_TINYBLOB,
        self::TYPE_MEDIUMBLOB,
        self::TYPE_BLOB,
        self::TYPE_LONGBLOB,
        self::TYPE_ENUM,
        self::TYPE_GEOMETRY,
        self::TYPE_POINT,
        self::TYPE_LINESTRING,
        self::TYPE_POLYGON,
    ];

    protected $relation;
    protected $change;

    // Default values

    public function __construct($table)
    {
        $this->relation = [];
        $this->table = $table;
        $this->change = false;
    }

    public function primary()
    {
        $this->setIndex(self::INDEX_PRIMARY);

        return $this;
    }
    
    public function increment()
    {
        $this->primary()->setIncrement(true);

        return $this;
    }

    public function index()
    {
        $this->setIndex(self::INDEX_INDEX);

        return $this;
    }

    public function nullable()
    {
        $this->setNullable(true);

        return $this;
    }

    public function string($length = 255)
    {
        $this->setType(self::TYPE_VARCHAR);
        $this->setLength($length);

        return $this;
    }

    public function integer($lenght = null)
    {
        $this->setType(self::TYPE_INT);
        $this->setLength($lenght);

        return $this;
    }

    public function bigint()
    {
        $this->setType(self::TYPE_BIGINT);

        return $this;
    }

    public function bool()
    {
        $this->setType(self::TYPE_TINYINT);
        $this->setNullable(true);
        $this->setLength(1);

        return $this;
    }

    public function text()
    {
        $this->setType(self::TYPE_TEXT);

        return $this;
    }

    public function longtext()
    {
        $this->setType(self::TYPE_LONGTEXT);

        return $this;
    }

    public function datetime()
    {
        $this->setType(self::TYPE_DATETIME);

        return $this;
    }

    public function date()
    {
        $this->setType(self::TYPE_DATE);

        return $this;
    }

    public function blob()
    {
        $this->setType(self::TYPE_LONGBLOB);

        return $this;
    }

    public function float()
    {
        $this->setType(self::TYPE_FLOAT);

        return $this;
    }

    public function double()
    {
        $this->setType(self::TYPE_DOUBLE);

        return $this;
    }

    public function decimal()
    {
        $this->setType(self::TYPE_DECIMAL);

        return $this;
    }

    public function timestamp()
    {
        $this->setType(self::TYPE_TIMESTAMP);

        return $this;
    }

    public function time()
    {
        $this->setType(self::TYPE_TIME);

        return $this;
    }

    public function drop()
    {
        Pdo::getInstance()->nonQuery('ALTER TABLE `' . $this->table . '` DROP COLUMN `' . $this->name . '`');
    }

    public function change()
    {
        $index = '';

        if ($this->getIndex() !== null) {
            $index = sprintf(', ADD %s (%s)', $this->getIndex(), $this->getName());
        }

        $query = 'ALTER TABLE ' . $this->table . ' MODIFY COLUMN ' . $this->getQuery() . $index . ';';
        PDO::getInstance()->nonQuery($query);
    }

    public function getQuery($includeRelations = true)
    {
        $length = '';
        if ($this->getLength()) {
            $length = '(' . $this->getLength() . ')';
        }

        $query = sprintf('%s %s%s %s', $this->getName(), $this->getType(), $length, $this->getAttributes());

        $query .= (!$this->getNullable()) ? 'not null' : 'null';

        if ($this->getIndex()) {
            $query .= sprintf(' %s', $this->getIndex(), $this->getName());
        }

        if ($this->getDefaultValue()) {
            $query .= PdoHelper::formatQuery(' default %s', [$this->getDefaultValue()]);
        }

        if ($this->getComment()) {
            $query .= PdoHelper::formatQuery(' comment %s', [$this->getComment()]);
        }

        if ($this->getIncrement()) {
            $query .= ' autoincrement';
        }

        if ($includeRelations) {

            
        }

        return $query;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setLength($length)
    {
        $this->length = $length;

        return $this;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function setDefaultValue($value)
    {
        $this->defaultValue = $value;

        return $this;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function setEncoding($encoding)
    {
        $this->encoding = $encoding;

        return $this;
    }

    public function getEncoding()
    {
        return $this->encoding;
    }

    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function setNullable($bool)
    {
        $this->nullable = $bool;

        return $this;
    }

    public function getNullable()
    {
        return $this->nullable;
    }

    public function setIndex($index)
    {
        $this->index = $index;

        return $this;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function setIncrement($increment)
    {
        $this->increment = $increment;

        $this->primary();

        return $this;
    }

    public function getIncrement()
    {
        return $this->increment;
    }

    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    public function getComment()
    {
        return $this->comment;
    }

}
