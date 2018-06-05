<?php
namespace Yuga\Database\Elegant;

use Closure;
use Carbon\Carbon;
use Yuga\Support\Str;
use Yuga\Http\Request;
use Yuga\Support\Inflect;
use Yuga\Pagination\Pagination;
use Yuga\Database\Elegant\Model;
use Yuga\Database\Elegant\Collection;
use Yuga\Database\Connection\Connection;
use Yuga\Database\Migration\Schema\Table;
use Yuga\Database\Query\Builder as QueryBuilder;
use Yuga\Database\Elegant\Association\Association;
use Yuga\Database\Elegant\Exceptions\ModelException;
use Yuga\Database\Elegant\Exceptions\ModelNotFoundException;

class Builder
{   
    /**
    * @var Model
    */
    protected $model;
    /**
    * @var QueryBuilder
    */
    protected $query;
    protected $results;
    protected $boot = [];
    protected $pagination;
    protected $withTrashed = false;
    protected $onlyTrashed = false;
    protected $returnWithRelations = false;
    protected $tempTable;
    public $table;


    public function __construct(Connection $connection, Model $model)
    {
        $this->model = $model;
        $this->query = (new QueryBuilder())->table($this->table ?: $this->model->getTable());
    }

    public function all($columns = null)
    {
        $models = $this->getAll($columns);
        return $models;
    }

    public function withTrashed()
    {
		$this->withTrashed = true;
		return $this;
	}

    public function onlyTrashed()
    {
		$this->onlyTrashed = true;
		return $this;
	}

    public function getAll($columns = null)
    {
        if ($this->getModel()->dispatchModelEvent('selecting', [$this->query, $this->getModel()]) === false) {
            return false;
        }
        if ($this->checkTableField($this->getModel()->getTable(), $this->getModel()->getDeleteKey())) {
            if ($this->withTrashed) {
				
			} elseif ($this->onlyTrashed) {
				$this->query->whereNotNull($this->getModel()->getDeleteKey());
			} else {
				$this->query->whereNull($this->getModel()->getDeleteKey());
			}
        }
        $models = $this->query->get($columns); 
        $results = $this->getModel()->makeModels($models, $this->boot);
        
        if(count($this->getModel()->relations) > 0){
			$models = $this->lazyLoadRelations($results->getItems());
		}
        $this->getModel()->dispatchModelEvent('selected', [$this->query, $results]);

        return $results;
    }

    public function lazyLoadRelations(array $models)
    {
		foreach ($this->getModel()->relations as $name => $constraints) {
            if (strpos($name, '.') === false) {
                $models = $this->loadRelation($models, $name, $constraints);
            }
        }
        return $models;
	}
    protected function loadRelation(array $models, $name, Closure $constraints)
    {
		$relation = $this->getRelation($name);
        $relation->addLazyConditions($models);

        //call_user_func($constraints, $relation);
		$models = $relation->bootRelation($models, $name);
		$results = $relation->getLazy();
		return $relation->match($models, $results, $name);
    }
    
    public function getRelation($name)
    {
		$relation = Association::noConditions(function () use ($name) {
            return $this->getModel()->$name();
        });
		return $relation;
    }
    
    /**
     * Get the deeply nested relations for a given top-level relation.
     *
     * @param  string  $relation
     * @return array
     */
    protected function nestedRelations($relation)
    {
        $nested = [];

        // We are basically looking for any relationships that are nested deeper than
        // the given top-level relationship. We will just check for any relations
        // that start with the given top relations and adds them to our arrays.
        foreach ($this->getModel()->relations as $name => $constraints) {
            if ($this->isNested($name, $relation)) {
                $nested[substr($name, strlen($relation.'.'))] = $constraints;
            }
        }

        return $nested;
    }

    /**
     * Determine if the relationship is nested.
     *
     * @param  string  $name
     * @param  string  $relation
     * @return bool
     */
    protected function isNested($name, $relation)
    {
        $dots = Str::contains($name, '.');

        return $dots && Str::startsWith($name, $relation.'.');
    }

    public function prefix($prefix)
    {
        $this->query->addPrefix($this->model->getTable(), $prefix);

        return $this;
    }

    public function limit($limit)
    {
        $this->query->limit($limit);

        return $this;
    }

    public function skip($skip)
    {
        $this->query->offSet($skip);

        return $this;
    }

    public function take($amount)
    {
        return $this->limit($amount);
    }

    public function offset($offset)
    {
        return $this->skip($offset);
    }

    public function setTable($table)
    {
        $this->tempTable = $table;
        $this->model->setTable($table);
        return $this;
    }

    public function where($key, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            if ($operator instanceof Closure) {
                $operator = $this->operatorClosure($operator, $key);
                extract($this->processKey($key));
                $key = $field;
            }
            $value = $operator;
            $operator = '=';
        }

        if ($value instanceof Closure) {
            $value = $this->valueClosure($value, $key);
            extract($this->processKey($key));
            $key = $field;
        }

        $this->query->where($key, $operator, $value);

        return $this;
    }

    protected function processKey($column) 
    {
        $columnAndField = [];
        $columnField = explode('.', $column);
        if (count($columnField) > 1) {
            $columnAndField['field'] = $columnField[1];
            $columnAndField['table'] = $columnField[0];
        } else {
            $columnAndField['field'] = $columnField[0];
            $columnAndField['table'] = null;
        }
        return $columnAndField;
    }

    protected function operatorClosure(Closure $closure, $column)
    {
        return $this->valueClosure($closure, $column);
    }

    protected function valueClosure(Closure $closure, $column)
    {
        extract($this->processKey($column));
        $model = clone $this->getModel()->setTable($table);
        $newQuery = $model->newElegantQuery();
        call_user_func($closure, $model);
        if (!$newQuery->query->getSelects()) {
            if ($table) {
                $field = strtolower(class_base($model)).'_'.$field;
            }
            $newQuery->select($field);
        }
        return $this->subQuery($model);
    }

    public function subQuery(Model $model, $alias = null)
    {
        return $this->query->subQuery($model->getQuery(), $alias);
    }

    public function getBindings()
    {
        return $this->query->getQuery()->getBindings();
    }
    public function whereIn($key, $values)
    {
        $this->query->whereIn($key, $values);

        return $this;
    }

    public function whereNot($key, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->query->whereNot($key, $operator, $value);

        return $this;
    }

    public function whereNotIn($key, $values)
    {
        $this->query->whereNotIn($key, $values);

        return $this;
    }

    public function whereNull($key)
    {
        $this->query->whereNull($key);

        return $this;
    }

    public function whereNotNull($key)
    {
        $this->query->whereNotNull($key);

        return $this;
    }

    public function whereBetween($key, $valueFrom, $valueTo)
    {
        $this->query->whereBetween($key, $valueFrom, $valueTo);

        return $this;
    }

    public function orWhere($key, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->query->orWhere($key, $operator, $value);

        return $this;
    }

    public function orWhereIn($key, $values)
    {
        $this->query->orWhereIn($key, $values);

        return $this;
    }

    public function orWhereNotIn($key, $values)
    {
        $this->query->orWhereNotIn($key, $values);

        return $this;
    }

    public function orWhereNot($key, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->query->orWhereNot($key, $operator, $value);

        return $this;
    }

    public function orWhereNull($key)
    {
        $this->query->orWhereNull($key);

        return $this;
    }

    public function orWhereNotNull($key)
    {
        $this->query->orWhereNotNull($key);

        return $this;
    }

    public function orWhereBetween($key, $valueFrom, $valueTo)
    {
        $this->query->orWhereBetween($key, $valueFrom, $valueTo);

        return $this;
    }

    public function get($columns = null)
    {
        return $this->all($columns);
    }


    public function find($ids, $columns = null)
    {
        if ($columns) {
            $this->select($columns);
        }
        if (!is_array($ids)) {
            $this->where($this->model->getPrimaryKey(), $ids);
            $item = $this->first();
            if ($item !== null) {
                return $item;
            }
        } else {
            $items = $this->whereIn($this->model->getPrimaryKey(), $ids)->get();
            return $items;
        }
        
        return null;
    }

    public function findOrFail($id)
    {
        $item = $this->find($id);
        if ($item === null) {
            throw new ModelNotFoundException(get_class($this->model) . ' was not found');
        }

        return $item;
    }

    public function first($columns = null)
    {
        if ($this->getModel()->dispatchModelEvent('selecting', [$this->query, $this->getModel()]) === false) {
            return false;
        }
        if ($columns) {
            $item = $this->query->select($columns)->first();
        } else {
            $item = $this->query->first();
        }
        
        if ($item !== null) {
            $model = $this->getModel()->newFromQuery($item, $this->boot);
            $model->setQuery($this);
            $model->dispatchModelEvent('selected', [$this->query, $model]);
            return $model;
        }
        return null;
    }

    public function last($columns = null)
    {
        if ($columns) {
            $item = $this->query->select($columns)->last();
        } else {
            $item = $this->query->last();
        }
        if ($item !== null) {
            return $this->model->newFromQuery($item);
        }
        return null;
    }

    public function firstOrFail($columns = null)
    {
        $item = $this->first($columns);
        if ($item === null) {
            throw new ModelNotFoundException(get_class($this->model) . ' was not found');
        }

        return $item;
    }

    public function count($field = '*')
    {
        return $this->query->count($field);
    }

    public function max($field)
    {
        $result = $this->query->select($this->query->raw('MAX(' . $field . ') AS max'))->get();
        return (int)$result[0]->max;
    }

    public function sum($field)
    {
        $result = $this->query->select($this->query->raw('SUM(' . $field . ') AS sum'))->get();
        return (int)$result[0]->sum;
    }

    protected function getValidData(array $data)
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $this->model->getColumns(), true) === true) {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    public function update(array $data = [])
    {
        if (count($data) === 0) {
            throw new ModelException('There are no valid columns found to update.');
        }

        $this->query->update($data);

        return $this->model;
    }

    public function create(array $data = [])
    {

        if (count($data) === 0) {
            throw new ModelException('There are no valid columns found to update.');
        }

        $id = $this->query->insert($data);

        if ($id) {

            $this->model->{$this->model->getPrimaryKey()} = $id;

            return $this->model;
        }

        return false;
    }

    public function firstOrCreate(array $attributes = [])
    {
        foreach($attributes as $attribute => $value){
			$this->where($attribute, $value);
        }
        
        if (!is_null($instance = $this->first())) {
            return $instance;
        }

        $instance = $this->getModel()->newInstance($attributes);

        $instance->save();

		return $instance;
    }

    public function firstOrNew(array $attributes = [])
    {
        foreach($attributes as $attribute => $value){
			$this->where($attribute, $value);
		}
        if (!is_null($instance = $this->first())) {
            return $instance;
        }

        return $this->getModel()->newInstance($attributes);
    }

    public function updateOrCreate(array $attributes, array $values = [])
    {
        $instance = $this->firstOrNew($attributes);

        $instance->fillModelWith($values)->save();

        return $instance;
    }

    public function destroy($ids)
    {
        $this->query->whereIn($this->getModel()->getPrimaryKey(), $ids)->delete();

        return $this->model;
    }

    public function select($fields)
    {
        $this->query->select($fields);

        return $this;
    }

    public function onTable($table)
    {
        $this->table = $table;
        return $this;
    }

    public function groupBy($field)
    {
        $this->query->groupBy($field);

        return $this;
    }

    public function orderBy($fields, $defaultDirection = 'ASC')
    {
        $this->query->orderBy($fields, $defaultDirection);

        return $this;
    }

    /**
     * Adds HAVING statement to the current query.
     *
     * @param string|Raw|\Closure $key
     * @param string|mixed $operator
     * @param string|mixed $value
     * @param string $type
     *
     * @return static
     */
    public function having($column, $operator = null, $value = null, $type = 'and')
    {
        if (func_num_args() == 2) {
			$valueTaken = $operator;
			$operatorSymbol = "=";
		} else {
			$valueTaken = $value;
			$operatorSymbol = $operator;
		}
        $this->query->having($column, $operatorSymbol, $valueTaken, $type);

        return $this;
    }

    /**
     * Adds OR HAVING statement to the current query.
     *
     * @param string|Raw|\Closure $key
     * @param string|mixed $operator
     * @param string|mixed $value
     * @param string $type
     *
     * @return static
     */
    public function orHaving($column, $operator = null, $value = null)
    {
		if (func_num_args() == 2) {
			$valueTaken = $operator;
			$operatorSymbol = "=";
		} else {
			$valueTaken = $value;
			$operatorSymbol = $operator;
		}
		return $this->having($column, $operatorSymbol, $valueTaken, 'or');
	}

    public function join($table, $key, $operator = null, $value = null, $type = 'inner')
    {
        $this->query->join($table, $key, $operator, $value, $type);

        return $this;
    }

    /**
     * Adds new LEFT JOIN statement to the current query.
     *
     * @param string|Raw|\Closure|array $table
     * @param string|Raw|\Closure $key
     * @param string|null $operator
     * @param string|Raw|\Closure|null $value
     *
     * @return static
     * @throws Exception
     */
    public function leftJoin($table, $key, $operator = null, $value = null)
    {
        $this->query->leftJoin($table, $key, $operator, $value);

        return $this;
    }

    /**
     * Adds new RIGHT JOIN statement to the current query.
     *
     * @param string|Raw|\Closure|array $table
     * @param string|Raw|\Closure $key
     * @param string|null $operator
     * @param string|Raw|\Closure|null $value
     *
     * @return static
     * @throws Exception
     */
    public function rightJoin($table, $key, $operator = null, $value = null)
    {
        $this->query->rightJoin($table, $key, $operator, $value);

        return $this;
    }

    public function raw($value, array $bindings = [])
    {
        return $this->query->raw($value, $bindings);
    }

    public function query($sql, array $bindings = [])
    {
        $this->query->query($sql, $bindings);
        return $this;
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param Model $model
     */
    public function setModel(Model $model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @return QueryBuilder
     */
    public function getQuery()
    {
        return $this->query;
    }

    public function toSql($raw = null)
    {
        if($raw == 'raw')
            return $this->getQuery()->getQuery()->getRawSql();
        return $this->getQuery()->getQuery()->getSql();
    }

    public function dates($updated_at, $created_at)
    {
		if(!$this->checkTableField($this->model->getTable(), $created_at)){
			$this->createTableField($this->model->getTable(), $created_at);
		}
		if(!$this->checkTableField($this->model->getTable(), $updated_at)){
			$this->createTableField($this->model->getTable(), $updated_at);
		}
    }
    
    public function checkTableField($table, $field)
    {
        $tableApi = '\Yuga\Database\Migration\Schema\\'.ucfirst(env('DATABASE_DRIVER', 'mysql')).'\\Table';
        return (new $tableApi($table))->columnExists($field);
    }

    public function createTableField($table, $field)
    {
        $tableApi = '\Yuga\Database\Migration\Schema\\'.ucfirst(env('DATABASE_DRIVER', 'mysql')).'\\Table';
        $table = new $tableApi($table);
        $table->column($field)->nullable()->datetime();
        $table->addColumns();
    }

    public function delete($permanent = false)
    {
		if($permanent == true){
			return $this->forceDelete();
		}
		return $this->softDelete();
	}

    private function forceDelete()
    {
		return $this->query->delete();
	}

    private function softDelete()
    {
		$time = Carbon::now()->toDateTimeString();
		
		if ($this->checkTableField($this->getModel()->getTable(), $this->getModel()->getDeleteKey())) {
            $this->update([$this->getModel()->getDeleteKey() => $time]);
		} else {
			$this->createTableField($this->getModel()->getTable(), $this->getModel()->getDeleteKey());
			$this->update([$this->getModel()->getDeleteKey() => $time]);
		}
		return $this->getModel();
	}

    public function __clone()
    {
        $this->query = clone $this->query;
    }

    public function with($relations)
    {
        if (is_string($relations)) {
            $relations = func_get_args();
        }

        
        $relations = $this->createWithRelations($relations);
        
        $this->getModel()->relations = array_merge($this->getModel()->relations, $relations);

        return $this;
    }

    protected function scanModelForFunctions(Model $model, array $functions = [])
    {
        $class = get_class($model);
        $scan = new \ReflectionClass($class);

        $modelMethods = $scan->getMethods();

        $validMethods = array_filter($modelMethods, function ($item) use ($functions) {
            return in_array($item->name, $functions);
        });
    }

    protected function createWithRelations($relations)
    {
    	$results = [];
        
        foreach ($relations as $name => $constraints) {
            if (is_numeric($name)) {
                $f = function () {
                    //
                };

                list($name, $constraints) = [$constraints, $f];
            }
            $results = $this->parseNestedWith($name, $results);
            $results[$name] = $constraints;
        }
        
        return $results;
    }

    /**
     * Parse the nested relationships in a relation.
     *
     * @param  string  $name
     * @param  array   $results
     * @return array
     */
    protected function parseNestedWith($name, $results)
    {
        $progress = [];
        
        foreach (explode('.', $name) as $segment) {
            $progress[] = $segment;
            if (!isset($results[$last = implode('.', $progress)])) {
                $results[$last] = function () {
                    //
                };
            }   
        }
        return $results;
    }

    public function paginate($limit = 10, array $options = null)
    {
        $request = new Request;
        $url = explode('?', $request->getUri());
        $page = 1;
        if (count($url) > 1) {
            $page = (int) $request->get('page') ? : 1;
            if ($options) {
                if (array_key_exists('url', $options)) {
                    $page = (int) $request->get($options['url']) ? : 1;
                }
            }
        }
        if($this->checkTableField($this->model->getTable(), 'deleted_at')){
            $this->whereNull('deleted_at');
        }
        $this->pagination = $pagination = new Pagination($page, $limit, $this->count());
        $results = $this->limit($limit)->offset($pagination->offset())->getWith(['pagination' => $this->pagination])->get();
        return $results;
    }

    public function getPagination()
    {
        return $this->pagination;
    }

    public function getWith(array $instances = [])
    {
        $this->boot = array_merge($this->boot, $instances);
        return $this;
    }

    /**
     * Get unique identifier for current query
     * @return string
     * @throws Exception
     */
    public function getQueryIdentifier()
    {
        return md5(static::class . $this->getSql(true));
    }

    public function __sleep()
    {
        return ['model'];
    }

    /**
     * @throws Exception
     */
    public function __wakeup()
    {
        $this->query = (new QueryBuilder())->table($this->model->getTable());
    }

    public function __destruct()
    {
        $this->query = null;
    }
}