<?php

namespace Yuga\Scaffold;

class Scaffold extends Types
{
    public static $METHODS = [
        self::TYPE_EMAIL => 'string',
        self::TYPE_MONTH => 'month',
        self::TYPE_HIDDEN => 'string',
        self::TYPE_PASSWORD => 'string',
        self::TYPE_FILE => 'string',
        self::TYPE_RANGE => 'integer',
        self::TYPE_CHECKBOX => 'bool',
        self::TYPE_VARCHAR => 'string',
        self::TYPE_LONGTEXT => 'longtext',
        self::TYPE_TEXT => 'text',
        self::TYPE_MEDIUMTEXT => 'textarea',
        self::TYPE_TINYTEXT => 'text',
        self::TYPE_INT => 'integer',
        self::TYPE_TINYINT => 'integer',
        self::TYPE_SMALLINT => 'integer',
        self::TYPE_MEDIUMINT => 'integer',
        self::TYPE_BIGINT => 'bigint',
        self::TYPE_DECIMAL => 'decimal',
        self::TYPE_FLOAT => 'float',
        self::TYPE_DOUBLE => 'double',
        self::TYPE_REAL => 'bigint',
        self::TYPE_BIT => 'integer',
        self::TYPE_BOOLEAN => 'bool',
        self::TYPE_SERIAL => 'text',
        self::TYPE_DATE => 'date',
        self::TYPE_DATETIME => 'datetime',
        self::TYPE_TIMESTAMP => 'time',
        self::TYPE_TIME => 'time',
        self::TYPE_YEAR => 'integer',
        self::TYPE_CHAR => 'string',
        self::TYPE_BINARY => 'bool',
        self::TYPE_VARBINARY => 'bool',
        self::TYPE_TINYBLOB => 'text',
        self::TYPE_MEDIUMBLOB => 'text',
        self::TYPE_BLOB => 'text',
        self::TYPE_LONGBLOB => 'text',
        self::TYPE_ENUM => 'string',
        self::TYPE_SET => 'string',
        self::TYPE_GEOMETRY => 'string',
        self::TYPE_POINT => 'string',
        self::TYPE_LINESTRING => 'string',
        self::TYPE_POLYGON => 'string',
        self::TYPE_MULTIPOINT => 'string',
        self::TYPE_MULTILINESTRING => 'string',
        self::TYPE_MULTIPOLYGON => 'string',
        self::TYPE_GEOMETRYCOLLECTION => 'string',
    ];

    public static function getMethod($type)
    {
        return self::$METHODS[$type];
    }

    public static function getFormType($type)
    {
        return self::$INPUT_TYPES[$type];
    }
}