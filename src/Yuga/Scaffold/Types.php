<?php

namespace Yuga\Scaffold;

class Types
{
    const TYPE_VARCHAR = 'VARCHAR';
    const TYPE_LONGTEXT = 'LONGTEXT';
    const TYPE_TEXT = 'TEXT';
    const TYPE_MEDIUMTEXT = 'MEDIUMTEXT';
    const TYPE_TINYTEXT = 'TINYTEXT';
    const TYPE_INT = 'INT';
    const TYPE_TINYINT = 'TINYINT';
    const TYPE_SMALLINT = 'SMALLINT';
    const TYPE_MEDIUMINT = 'MEDIUMINT';
    const TYPE_BIGINT = 'BIGINT';
    const TYPE_DECIMAL = 'DECIMAL';
    const TYPE_FLOAT = 'FLOAT';
    const TYPE_DOUBLE = 'DOUBLE';
    const TYPE_REAL = 'REAL';
    const TYPE_BIT = 'BIT';
    const TYPE_BOOLEAN = 'BOOLEAN';
    const TYPE_SERIAL = 'SERIAL';
    const TYPE_DATE = 'DATE';
    const TYPE_DATETIME = 'DATETIME';
    const TYPE_TIMESTAMP = 'TIMESTAMP';
    const TYPE_TIME = 'TIME';
    const TYPE_YEAR = 'YEAR';
    const TYPE_CHAR = 'CHAR';
    const TYPE_BINARY = 'BINARY';
    const TYPE_VARBINARY = 'VARBINARY';
    const TYPE_TINYBLOB = 'TINYBLOB';
    const TYPE_MEDIUMBLOB = 'MEDIUMBLOB';
    const TYPE_BLOB = 'BLOB';
    const TYPE_LONGBLOB = 'LONGBLOB';
    const TYPE_ENUM = 'ENUM';
    const TYPE_SET = 'SET';
    const TYPE_GEOMETRY = 'GEOMETRY';
    const TYPE_POINT = 'POINT';
    const TYPE_LINESTRING = 'LINESTRING';
    const TYPE_POLYGON = 'POLYGON';
    const TYPE_MULTIPOINT = 'MULTIPOINT';
    const TYPE_MULTILINESTRING = 'MULTILINESTRING';
    const TYPE_MULTIPOLYGON = 'MULTIPOLYGON';
    const TYPE_GEOMETRYCOLLECTION = 'GEOMETRYCOLLECTION';
    const TYPE_EMAIL = 'EMAIL';
    const TYPE_MONTH = 'MONTH';
    const TYPE_CHECKBOX = 'CHECKBOX';
    const TYPE_PASSWORD = 'PASSWORD';
    const TYPE_HIDDEN = 'HIDDEN';
    const TYPE_FILE = 'FILE';
    const TYPE_RANGE = 'RANGE';
    const TYPE_EDITOR = 'EDITOR';

    public static $INPUT_TYPES = [
        self::TYPE_EMAIL => 'email',
        self::TYPE_MONTH => 'month',
        self::TYPE_HIDDEN => 'hidden',
        self::TYPE_PASSWORD => 'password',
        self::TYPE_FILE => 'file',
        self::TYPE_RANGE => 'range',
        self::TYPE_CHECKBOX => 'checkbox',
        self::TYPE_VARCHAR => 'text',
        self::TYPE_LONGTEXT => 'textarea',
        self::TYPE_TEXT => 'textarea',
        self::TYPE_MEDIUMTEXT => 'textarea',
        self::TYPE_TINYTEXT => 'text',
        self::TYPE_INT => 'number',
        self::TYPE_TINYINT => 'integer',
        self::TYPE_SMALLINT => 'integer',
        self::TYPE_MEDIUMINT => 'number',
        self::TYPE_BIGINT => 'number',
        self::TYPE_DECIMAL => 'number',
        self::TYPE_FLOAT => 'number',
        self::TYPE_DOUBLE => 'number',
        self::TYPE_REAL => 'number',
        self::TYPE_BIT => 'number',
        self::TYPE_BOOLEAN => 'radio',
        self::TYPE_SERIAL => 'text',
        self::TYPE_DATE => 'date',
        self::TYPE_DATETIME => 'datetime-local',
        self::TYPE_TIMESTAMP => 'time',
        self::TYPE_TIME => 'time',
        self::TYPE_YEAR => 'number',
        self::TYPE_CHAR => 'text',
        self::TYPE_BINARY => 'radio',
        self::TYPE_VARBINARY => 'radio',
        self::TYPE_TINYBLOB => 'file',
        self::TYPE_MEDIUMBLOB => 'file',
        self::TYPE_BLOB => 'file',
        self::TYPE_LONGBLOB => 'file',
        self::TYPE_ENUM => 'range',
        self::TYPE_SET => 'range',
        self::TYPE_GEOMETRY => 'range',
        self::TYPE_POINT => 'number',
        self::TYPE_LINESTRING => 'text',
        self::TYPE_POLYGON => 'text',
        self::TYPE_MULTIPOINT => 'text',
        self::TYPE_MULTILINESTRING => 'text',
        self::TYPE_MULTIPOLYGON => 'text',
        self::TYPE_GEOMETRYCOLLECTION => 'text',
        self::TYPE_EDITOR => 'editor',
    ];
}