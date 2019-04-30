<?php
namespace Yuga\Database\Query;

use PDO;
use Closure;
use Exception;
use Yuga\Database\Elegant\Collection;
use Yuga\Database\Connection\Connection;
use Yuga\Database\Query\Exceptions\DatabaseQueryException;

class Builder
{
    protected $pdo;
    protected $container;
    protected $connection;
    protected $tablePrefix;
    protected $pdoStatement;
    protected $statements = [];
    protected $grammarInstance;
    protected $fetchParameters = [PDO::FETCH_OBJ];
    protected $acceptableTypes = ['select', 'insert', 'insertignore', 'replace', 'delete', 'update', 'criteriaonly'];
    
    public function __construct(Connection $connection = null)
    {
        if ($connection === null && ($connection = Connection::getStoredConnection()) === false) {
            throw new Exception('No database connection found.', 1);
        }
        $this->connection = $connection;
        $this->container = $this->connection->getContainer();
        $this->pdo = $this->connection->getPdoInstance();
        $this->adapter = $this->connection->getAdapter();
        $this->adapterConfig = $this->connection->getAdapterConfig();
        if (isset($this->adapterConfig['prefix'])) {
            $this->tablePrefix = $this->adapterConfig['prefix'];
        }
        // Query builder grammar instance
        $this->grammarInstance = $this->container->resolve(
            '\Yuga\Database\Query\Grammar\\' . ucfirst($this->adapter),
            [$this->connection]
        );
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public function getPdo()
    {
        return $this->pdo;
    }

    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
        return $this;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function table($tables)
    {
        if (!is_array($tables)) {
            // change supplied parameters to an array so as to capture tables as an array,
            $tables = func_get_args();
        }
        $instance = new static($this->connection);
        $tables = $this->addTablePrefix($tables, false);
        $instance->addStatement('tables', $tables);
        return $instance;
    }

    protected function addTablePrefix($values, $tableFieldMix = true)
    {
        if($this->tablePrefix === null) {
            return $values;
        }
        $single = false;
        // change all values to an array if not already an array
        if (!is_array($values)) {
            $values = [$values];
            // We had single value, so should return a single value
            $single = true;
        }
        $return = [];
        foreach ($values as $key => $value) {
            // It's a raw query, just add it to our return array and continue next
            if ($value instanceof Raw || $value instanceof \Closure) {
                $return[$key] = $value;
                continue;
            }

            // If key is not integer, it is likely a alias mapping, so we need to change prefix target
            $target = &$value;
            if (is_int($key) === false) {
                $target = &$key;
            }
            if ($tableFieldMix === false || ($tableFieldMix && strpos($target, '.') !== false)) {
                $target = $this->tablePrefix . $target;
            }
            $return[$key] = $value;
        }
        // If we had single value then we should return a single value (end value of the array)
        return $single ? end($return) : $return;
    }
    protected function addStatement($key, $value)
    {
        if(!array_key_exists($key, $this->statements)){
            $this->statements[$key] = (array)$value;
        }else{
            $this->statements[$key] = array_merge($this->statements[$key], (array)$value);
        }
    }

    /**
     * @param Builder $builder
     * @param null $alias
     * @throws Exception
     * @return Raw
     */
    public function subQuery(Builder $builder, $alias = null)
    {
        $sql = '(' . $builder->getQuery()->getRawSql() . ')';
        if ($alias) {
            $sql = $sql . ' as ' . $alias;
        }
        return $builder->raw($sql);
    }

    /**
     * Add a raw query
     *
     * @param string $value
     * @param array $bindings
     *
     * @return Raw
     */
    public function raw($value, $bindings = [])
    {
        return $this->container->resolve(Raw::class, [$value, $bindings]);
    }

    public function select($fields)
    {
        if (is_array($fields) === false) {
            $fields = func_get_args();
        }
        $fields = $this->addTablePrefix($fields);
        $this->addStatement('selects', $fields);
        return $this;
    }

    public function selectDistinct($fields)
    {
        $this->select($fields);
        $this->addStatement('distinct', true);
        return $this;
    }

    public function orderBy($fields, $defaultDirection = 'ASC')
    {
        if (is_array($fields) === false) {
            $fields = [$fields];
        }
        foreach ($fields as $key => $value) {
            $field = $key;
            $type = $value;
            if (is_int($key)) {
                $field = $value;
                $type = $defaultDirection;
            }
            if (!$field instanceof Raw) {
                $field = $this->addTablePrefix($field);
            }
            $this->statements['orderBys'][] = compact('field', 'type');
        }
        return $this;
    }

    public function groupBy($field)
    {
        $field = $this->addTablePrefix($field);
        $this->addStatement('groupBys', $field);
        return $this;
    }

    public function limit($lower, $upper = null)
    {
        if(!is_null($upper))
            $this->statements['limit'] = $lower .', '. $upper;
        else
            $this->statements['limit'] = $lower;
        return $this;
    }

    public function take($number)
    {
        $this->limit($number);
        return $this;
    }

    public function offSet($offset)
    {
        $this->statements['offset'] = $offset;
        return $this;
    }

    public function getStatements()
    {
        return $this->statements;
    }

    protected function whereHandler($column, $operator = null, $value = null, $type = 'AND')
    {
        $key = $this->addTablePrefix($column);
        $this->statements['wheres'][] = compact('column', 'operator', 'value', 'type');
        return $this;
    }

    public function where($column, $operator = null, $value = null)
    {
        // If two params are given then assume operator is =
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        if (is_bool($value)) {
            $value = (int)$value;
        }
        return $this->whereHandler($column, $operator, $value);
    }

    public function orWhere($column, $operator = null, $value = null)
    {
        // If two params are given then assume operator is =
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return $this->whereHandler($column, $operator, $value, 'or');
    }

    public function getQuery($type = 'select', $dataToBePassed = [])
    {
        if (in_array(strtolower($type), $this->acceptableTypes, true) === false) {
            throw new Exception($type . ' is not a known type.', 2);
        }
        $queryArr = $this->grammarInstance->$type($this->statements, $dataToBePassed);
        return $this->container->resolve(
            QueryObject::class,
            [$queryArr['sql'], $queryArr['bindings'], $this->getConnection()]
        );
    }

    public function whereIn($key, $values)
    {
        if(!is_array($values)){
			$values = [0];
		}

		if(count($values) < 1){
			$values = [0];
		}
        return $this->whereHandler($key, 'IN', $values, 'and');
    }

    public function whereNotIn($key, $values)
    {
        if(!is_array($values)){
			$values = [0];
		}

		if(count($values) < 1){
			$values = [0];
		}
        return $this->whereHandler($key, 'NOT IN', $values, 'and');
    }

    public function orWhereIn($key, $values)
    {
        if(!is_array($values)){
			$values = [0];
		}

		if(count($values) < 1){
			$values = [0];
		}
        return $this->whereHandler($key, 'IN', $values, 'or');
    }

    public function orWhereNotIn($key, $values)
    {
        if(!is_array($values)){
			$values = [0];
		}

		if(count($values) < 1){
			$values = [0];
		}
        return $this->whereHandler($key, 'NOT IN', $values, 'or');
    }

    public function whereBetween($key, $valueFrom, $valueTo)
    {
        return $this->whereHandler($key, 'BETWEEN', [$valueFrom, $valueTo], 'and');
    }

    public function orWhereBetween($key, $valueFrom, $valueTo)
    {
        return $this->whereHandler($key, 'BETWEEN', [$valueFrom, $valueTo], 'or');
    }

    public function whereNull($key)
    {
        return $this->whereNullHandler($key);
    }

    public function whereNotNull($key)
    {
        return $this->whereNullHandler($key, 'NOT');
    }

    public function orWhereNull($key)
    {
        return $this->whereNullHandler($key, '', 'or');
    }

    public function orWhereNotNull($key)
    {
        return $this->whereNullHandler($key, 'NOT', 'or');
    }

    protected function whereNullHandler($key, $prefix = '', $operator = '')
    {
        $key = $this->grammarInstance->wrapSanitizer($this->addTablePrefix($key));
        return $this->{$operator . 'Where'}($this->raw("{$key} IS {$prefix} NULL"));
    }

    public function having($column, $operator, $value, $type = 'and')
    {
        $column = $this->addTablePrefix($column);
        $this->statements['havings'][] = compact('column', 'operator', 'value', 'type');
        return $this;
    }

    public function orHaving($key, $operator, $value)
    {
        return $this->having($key, $operator, $value, 'or');
    }

    public function asObject($className, array $constructorArgs = [])
    {
        return $this->setFetchMode(PDO::FETCH_CLASS, $className, $constructorArgs);
    }

    /**
     * Set the fetch mode
     *
     * @param string $mode
     * @return static
     */
     public function setFetchMode($mode)
     {
         $this->fetchParameters = func_get_args();
         return $this;
     }

    public function newQuery(Connection $connection = null)
    {
        if ($connection === null) {
            $connection = $this->connection;
        }
        return new static($connection);
    }

    public function query($sql, array $bindings = [])
    {
        $queryObject = new QueryObject($sql, $bindings, $this->getConnection());
        $this->connection->setLastQuery($queryObject);
        list($this->pdoStatement) = $this->statement($sql, $bindings);
        return $this;
    }

    public function alias($table, $alias)
    {
        $this->statements['tables'][$this->tablePrefix . $table] =  strtolower($alias);
        return $this;
    }

    public function join($table, $column, $operator = null, $value = null, $joinType = 'inner')
    {
        if (($column instanceof Closure) === false) {
            $column = function (JoinBuilder $joinBuilder) use ($column, $operator, $value) {
                $joinBuilder->on($column, $operator, $value);
            };
        }
        // Build a new JoinBuilder class, keep it by reference so any changes made
        // in the closure should reflect here
        $joinBuilder = $this->container->resolve(JoinBuilder::class, [$this->connection]);

        // Call the closure with our new joinBuilder object
        $column($joinBuilder);
        $table = $this->addTablePrefix($table, false);
        // Get the criteria only query from the joinBuilder object
        $this->statements['joins'][] = compact('joinType', 'table', 'joinBuilder');
        return $this;
    }

    public function leftJoin($table, $key, $operator = null, $value = null)
    {
        return $this->join($table, $key, $operator, $value, 'left');
    }

    public function rightJoin($table, $key, $operator = null, $value = null)
    {
        return $this->join($table, $key, $operator, $value, 'right');
    }

    public function innerJoin($table, $key, $operator = null, $value = null)
    {
        return $this->join($table, $key, $operator, $value, 'inner');
    }

    /**
     * @param       $sql
     * @param array $bindings
     *
     * @return array PDOStatement and execution time as float
     */
    public function statement($sql, $bindings = [])
    {
        try {
            $pdoStatement = $this->pdo->prepare($sql); 
            foreach ($bindings as $key => $value) {
                $pdoStatement->bindValue(
                    is_int($key) ? $key + 1 : $key,
                    $value,
                    is_int($value) || is_bool($value) ? PDO::PARAM_INT : PDO::PARAM_STR
                );
            }
            $pdoStatement->execute();
            return [$pdoStatement];
        } catch (\PDOException $ex) {
            throw DatabaseQueryException::create($ex, $this->getConnection()->getAdapterInstance()->getQueryAdapterClass(), $this->getLastQuery());
        }
    }

    /**
     * Get query-object from last executed query.
     *
     * @return QueryObject|null
     */
    public function getLastQuery()
    {
        return $this->connection->getLastQuery();
    }

    /**
     * Get all rows
     * @throws Exception
     * @return \stdClass|array|null
     */
    public function get($columns = null)
    {
        return new Collection($this->getAll($columns));
    }

    public function getAll($columns = null)
    {
        if ($this->pdoStatement === null) {
            if($columns)
                $this->select($columns);
            $queryObject = $this->getQuery('select');
            $this->connection->setLastQuery($queryObject);
            list($this->pdoStatement) = $this->statement($queryObject->getSql(), $queryObject->getBindings());
        }
        $result = call_user_func_array([$this->pdoStatement, 'fetchAll'], $this->fetchParameters);
        $this->pdoStatement = null;
        return $result;
    }
    
    /**
     * Alias to get
     */
    public function all($columns = null)
    {
        return $this->get($columns);
    }
    public function first()
    {
        $this->take(1);
        $result = $this->getAll();
        return empty($result) ? null : $result[0];
    }

    public function last()
    {
        $result = $this->getAll();
        return empty($result) ? null : array_pop($result);
    }

    /**
     * @param        $value
     * @param string $fieldName
     * @throws Exception
     * @return null|\stdClass
     */
    public function findAll($fieldName, $value)
    {
        $this->where($fieldName, '=', $value);
        return $this->get();
    }

    /**
     * @param        $value
     * @param string $fieldName
     * @throws Exception
     * @return null|\stdClass
     */
    public function find($value, $fieldName = 'id')
    {
        $this->where($fieldName, '=', $value);
        return $this->first();
    }

    /**
     * Get count of rows
     * @throws Exception
     * @return int
     */
    public function count()
    {
        // Get the current statements
        $originalStatements = $this->statements;
        unset($this->statements['orderBys'], $this->statements['limit'], $this->statements['offset']);
        $count = $this->aggregate('count');
        $this->statements = $originalStatements;
        return $count;
    }

    public function getSelects()
    {
        if (isset($this->statements['selects']))
            return $this->statements['selects'];
        return null;
    }

    /**
     * @param $type
     * @throws Exception
     * @return int
     */
    protected function aggregate($type)
    {
        // Get the current selects
        $mainSelects = isset($this->statements['selects']) ? $this->statements['selects'] : null;
        // Replace select with a scalar value like `count`
        $this->statements['selects'] = [$this->raw($type . '(*) as field')];
        $row = $this->get();

        // Set the select as it was
        if ($mainSelects) {
            $this->statements['selects'] = $mainSelects;
        } else {
            unset($this->statements['selects']);
        }

        if (isset($row[0])) {
            if (is_array($row[0])) {
                return (int)$row[0]['field'];
            } elseif (is_object($row[0])) {
                return (int)$row[0]->field;
            }
        }
        return 0;
    }

    public function insert($data)
    {
        return $this->doInsert($data, 'insert');
    }

    /**
     * @param $data
     * @throws Exception
     * @return array|string
     */
    public function insertIgnore($data)
    {
        return $this->doInsert($data, 'insertignore');
    }

    /**
     * @param $data
     * @throws Exception
     * @return array|string
     */
    public function replace($data)
    {
        return $this->doInsert($data, 'replace');
    }

    /**
     * @param string $data
     * @throws Exception
     * @return static
     */
    public function update($data)
    {

        $queryObject = $this->getQuery('update', $data);

        $this->connection->setLastQuery($queryObject);

        list($response) = $this->statement($queryObject->getSql(), $queryObject->getBindings());
        
        return $response;
    }

    /**
     * @param $data
     * @throws Exception
     * @return array|string
     */
    public function updateOrInsert($data)
    {
        if ($this->first()) {
            return $this->update($data);
        } else {
            return $this->insert($data);
        }
    }

    /**
     * @param string $data
     * @return static
     */
    public function onDuplicateKeyUpdate($data)
    {
        $this->addStatement('onduplicate', $data);

        return $this;
    }

    /**
     * @throws Exception
     */
    public function delete()
    {
        $queryObject = $this->getQuery('delete');
        $this->connection->setLastQuery($queryObject);
        list($response) = $this->statement($queryObject->getSql(), $queryObject->getBindings());

        return $response;
    }

    /**
     * @param array $data
     * @param string $type
     * @throws Exception
     * @return array|string
     */
    private function doInsert($data, $type)
    {
        // If first value is not an array
        // Its not a batch insert
        if (!is_array(current($data))) {
            $queryObject = $this->getQuery($type, $data);

            $this->connection->setLastQuery($queryObject);

            list($result) = $this->statement($queryObject->getSql(), $queryObject->getBindings());

            $return = $result->rowCount() === 1 ? $this->pdo->lastInsertId() : null;
        } else {
            // Its a batch insert
            $return = [];
            foreach ($data as $subData) {
                $queryObject = $this->getQuery($type, $subData);

                list($result, $time) = $this->statement($queryObject->getSql(), $queryObject->getBindings());

                if ($result->rowCount() === 1) {
                    $return[] = $this->pdo->lastInsertId();
                }
            }
        }

        return $return;
    }  
}