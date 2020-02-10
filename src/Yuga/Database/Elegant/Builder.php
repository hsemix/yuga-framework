<?php
namespace Yuga\Database\Elegant;

use Closure;
use Carbon\Carbon;
use ReflectionClass;
use Yuga\Support\Str;
use Yuga\Http\Request;
use Yuga\Support\Inflect;
use Yuga\Pagination\Paginator;
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
    public $table;

    /**
     * Make Builder instance
     * 
     * @param Connection|null $connection
     * @param Model $model
     * 
     * @return void
     */
    public function __construct(Connection $connection = null, Model $model)
    {
        $this->model = $model;
        $this->query = (new QueryBuilder)->table($this->model->getTable());
    }

    /**
     * Return all results or the queried ones
     * 
     * @param array|null $columns
     * 
     * @return Collection
     */
    public function all($columns = null)
    {
        $models = $this->getAll($columns);
        return $models;
    }

    /**
     * Return all results including trashed ones
     * 
     * @param null
     * 
     * @return static
     */
    public function withTrashed()
    {
		$this->withTrashed = true;
		return $this;
	}

    /**
     * Return only Trashaed records i.e with deleted_at not null
     * 
     * @param null
     * 
     * @return static
     */
    public function onlyTrashed()
    {
		$this->onlyTrashed = true;
		return $this;
	}

    /**
     * Query the database and return the results as model instances
     * 
     * @param array|null $columns
     * 
     * @return Collection
     */
    public function getAll($columns = null)
    {
        $carbon = \Yuga\Carbon\Carbon::class;
        if (class_exists(Carbon::class)) {
            $carbon = Carbon::class;
        }
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
        $models = $this->query->getAll($columns); 
        $models = array_map(function ($model) use ($carbon) {
            if($this->checkTableField($this->getModel()->getTable(), $this->getModel()->getCreatedAtColumn())){
                if (property_exists($model, $this->getModel()->getCreatedAtColumn())) {
                    if ($model->{$this->getModel()->getCreatedAtColumn()}) {
                        $model->{$this->getModel()->getCreatedAtColumn()} = new $carbon($model->{$this->getModel()->getCreatedAtColumn()}); 
                    }
                }
            }
            if($this->checkTableField($this->getModel()->getTable(), $this->getModel()->getUpdatedAtColumn())){
                if (property_exists($model, $this->getModel()->getUpdatedAtColumn())) {
                    if ($model->{$this->getModel()->getUpdatedAtColumn()}) {
                        $model->{$this->getModel()->getUpdatedAtColumn()} = new $carbon($model->{$this->getModel()->getUpdatedAtColumn()}); 
                    }
                }
            }
            return $model;
        }, $models);
        $results = $this->getModel()->makeModels($models, $this->boot);
        
        $this->getModel()->dispatchModelEvent('selected', [$this->query, $results]);

        return $results;
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
        $table = is_object($table) ? $table->getTable() : $table;
        $this->table = $table;
        $this->query = $this->query->table($table);
        return $this;
    }

    public function getTable()
    {
        return $this->table;
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
        $model = clone $this->getModel()->setTable(is_object($table) ? $table->getTable() : $table);
        $newQuery = $model->newElegantQuery();
        call_user_func($closure, $newQuery);
        if (!$newQuery->query->getSelects()) {
            $classTable = strtolower(class_base($model));
            $modelTable = is_object($newQuery->getTable()) ? $newQuery->getModel()->getTable() : $newQuery->getTable();
            if (($table || $modelTable) && ($colon = strpos($field, $classTable.'_')) === false) {
                $field = $classTable.'_'.$field;  
            }
            $newQuery->select($field);
        }

        if ($this->checkTableField($newQuery->getTable() ? is_object($newQuery->getTable()) ? $newQuery->getModel()->getTable() : $newQuery->getTable() : $model->getTable(), is_object($newQuery->getTable()) ? $newQuery->getModel()->getDeleteKey() : $model->getDeleteKey())) {
            if ($this->withTrashed) {
                
            } elseif ($this->onlyTrashed) {
                $newQuery->whereNotNull($model->getDeleteKey());
            } else {
                $newQuery->whereNull($model->getDeleteKey());
            }
        }
        return $this->subQuery($model);
    }

    public function subQuery($model, $alias = null) // removed contract of Model from $model
    {
        return $this->query->subQuery($model->getQuery(), $alias);
    }

    public function getBindings()
    {
        return $this->query->getQuery()->getBindings();
    }
    public function whereIn($key, $values)
    {
        if (count($values) == 0)
            $values[] = 0;
       return $this->where($key, 'IN', $values);
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
        if (count($values) == 0)
            $values[] = 0;
        return $this->where($key, 'NOT IN', $values);
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

        $this->query->orWhere($key, $operator, $value);

        return $this;
    }

    public function orWhereIn($key, $values)
    {
        if (count($values) == 0)
            $values[] = 0;
        $this->orWhere($key, 'IN', $values);
        return $this;
    }

    public function orWhereNotIn($key, $values)
    {
        if (count($values) == 0)
            $values[] = 0;
        $this->orWhere($key, 'NOT IN', $values);
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
        $carbon = \Yuga\Carbon\Carbon::class;
        if (class_exists(Carbon::class)) {
            $carbon = Carbon::class;
        }
        if ($this->getModel()->dispatchModelEvent('selecting', [$this->query, $this->getModel()]) === false) {
            return false;
        }
        if ($columns) {
            $item = $this->query->select($columns)->first();
        } else {
            $item = $this->query->first();
        }
        
        if ($item !== null) {
            
            if($this->checkTableField($this->getModel()->getTable(), $this->getModel()->getCreatedAtColumn())){
                if (property_exists($item, $this->getModel()->getCreatedAtColumn())) {
                    if ($item->{$this->getModel()->getCreatedAtColumn()}) {
                        $item->{$this->getModel()->getCreatedAtColumn()} = new $carbon($item->{$this->getModel()->getCreatedAtColumn()}); 
                    }
                }
            }
            if($this->checkTableField($this->getModel()->getTable(), $this->getModel()->getUpdatedAtColumn())){
                if (property_exists($item, $this->getModel()->getUpdatedAtColumn())) {
                    if ($item->{$this->getModel()->getUpdatedAtColumn()}) {
                        $item->{$this->getModel()->getUpdatedAtColumn()} = new $carbon($item->{$this->getModel()->getUpdatedAtColumn()}); 
                    }
                }
            }
            $model = $this->getModel()->newFromQuery($item, $this->boot);
            // $model->setQuery($this);
            $model->dispatchModelEvent('selected', [$this->query, $model]);
            return $model;
        }
        return $item;
    }

    public function last($columns = null)
    {
        $carbon = \Yuga\Carbon\Carbon::class;
        if (class_exists(Carbon::class)) {
            $carbon = Carbon::class;
        }
        if ($columns) {
            $item = $this->query->select($columns)->last();
        } else {
            $item = $this->query->last();
        }
        if ($item !== null) {
            if($this->checkTableField($this->getModel()->getTable(), $this->getModel()->getCreatedAtColumn())){
                if (property_exists($item, $this->getModel()->getCreatedAtColumn())) {
                    if ($item->{$this->getModel()->getCreatedAtColumn()}) {
                        $item->{$this->getModel()->getCreatedAtColumn()} = new $carbon($item->{$this->getModel()->getCreatedAtColumn()}); 
                    }
                }
            }
            if($this->checkTableField($this->getModel()->getTable(), $this->getModel()->getUpdatedAtColumn())){
                if (property_exists($item, $this->getModel()->getUpdatedAtColumn())) {
                    if ($item->{$this->getModel()->getUpdatedAtColumn()}) {
                        $item->{$this->getModel()->getUpdatedAtColumn()} = new $carbon($item->{$this->getModel()->getUpdatedAtColumn()}); 
                    }
                }
            }
            return $this->getModel()->newFromQuery($item, $this->boot);
        }
        return $item;
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
        $result = $this->query->select($this->query->raw('MAX(' . $field . ') AS max'))->getAll();
        return (int)$result[0]->max;
    }

    public function sum($field)
    {
        $result = $this->query->select($this->query->raw('SUM(' . $field . ') AS sum'))->getAll();
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
        if (is_array($fields) === false) {
            $fields = func_get_args();
        }
        $this->query->select($fields);

        return $this;
    }

    /**
     * An alias of setTable
     * 
     * @param string|array $table
     * 
     * @return QueryBuilder $query
     */
    public function from($table)
    {
        return $this->setTable($table);
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
    public function getModel(Pagination $pagination = null)
    {
        if ($pagination)
            $this->model->setPagination($pagination);
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

    /**
     * Return a printable representation of the Builder object
     * 
     * @param string|null $raw
     * 
     * @return string
     */
    public function toSql($raw = null)
    {
        if($raw == 'raw')
            return $this->getQuery()->getQuery()->getRawSql();
        return $this->getQuery()->getQuery()->getSql();
    }

    /**
     * Create dates for a created_at and updated_at
     * 
     * @param string $updated_at
     * @param string $created_at
     * 
     * @return void
     */
    public function dates($updated_at, $created_at)
    {
		if(!$this->checkTableField($this->model->getTable(), $created_at)){
			$this->createTableField($this->model->getTable(), $created_at);
		}
		if(!$this->checkTableField($this->model->getTable(), $updated_at)){
			$this->createTableField($this->model->getTable(), $updated_at);
		}
    }
    
    /**
     * Check a database table for a given field
     * 
     * @param string $table
     * @param string $field
     * 
     * @return boolean
     */
    public function checkTableField($table, $field)
    {
        $tableApi = '\Yuga\Database\Migration\Schema\\'.ucfirst(env('DATABASE_DRIVER', 'mysql')).'\\Table';
        return (new $tableApi($table))->columnExists($field);
    }

    /**
     * Create a new field in a database table i.e. columns for managing dates
     * 
     * @param string $table
     * @param string $field
     * 
     * @return void
     */
    public function createTableField($table, $field)
    {
        $tableApi = '\Yuga\Database\Migration\Schema\\'.ucfirst(env('DATABASE_DRIVER', 'mysql')).'\\Table';
        $table = new $tableApi($table);
        $table->column($field)->nullable()->datetime();
        $table->addColumns();
    }

    /**
     * Perform a delete on a record i.e. either permanent or soft
     * 
     * @param boolean|false $permanent
     * 
     * @return mixed
     */
    public function delete($permanent = false)
    {
		if($permanent == true){
			return $this->forceDelete();
		}
		return $this->softDelete();
	}

    /**
     * Permanently Delete a record from a table
     * 
     * @param null
     * 
     * @return mixed
     */
    private function forceDelete()
    {
		return $this->query->delete();
	}

    /**
     * Soft delete a record i.e. create a deleted_at key and populate it with date
     * 
     * @param null
     * 
     * @return Model
     */
    private function softDelete()
    {
        $carbon = \Yuga\Carbon\Carbon::class;
        if (class_exists(Carbon::class)) {
            $carbon = Carbon::class;
        }
		$time = $carbon::now()->toDateTimeString();
		
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
        // $this->query = $this->query;
        // $this->query = clone $this->query;
    }
    
    /**
     * Format results and include whatever the user whats to be included in the results before being returned
     * 
     * @param array ...$relations
     * 
     * @return static
     */
    public function with($relations)
    {
        $bootable = [];
        if (is_string($relations)) {
            $relations = func_get_args();
        }
        foreach ($relations as $key => $value) {
            if (is_string($value) === true && is_numeric($key) === true) {
                $bootable[$value] = $value;
            } else {
                $bootable[$key] = $value;
            }
        }
        $this->boot = $this->getModel()->bootable = $bootable;

        return $this;
    }

    /**
     * Scan a model for valid methods
     * 
     * @param Model $model
     * @param array|[] $methods
     */
    protected function scanModelForMethods(Model $model, array $methods = [])
    {
        $class = get_class($model);
        $scan = new ReflectionClass($class);

        $modelMethods = $scan->getMethods();

        $validMethods = array_filter($modelMethods, function ($item) use ($methods) {
            return in_array($item->name, $methods);
        });
    }

    /**
     * Paginate All results as they are returned
     * 
     * @param int $limit
     * @param array|null $options
     * 
     * @return Collection $results
     */
    public function simplePaginate($limit = 10, array $options = null)
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

    /**
     * Paginate results
     * 
     * @param int $perPage
     * @param int $per
     * @param string $pageName
     */
    public function paginate($perPage = null, $page = null, $pageName = 'page', $pathName = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $perPage = $perPage ?: $this->model->getPerPage();

        $paths = explode('?', (new Request)->getUri(true));

        $this->pagination = $pagination = new Pagination($page, $perPage, $this->count(), $pageName);
        $options = [
            'path' => $pathName ?: $paths[0],
            'pageName' => $pageName,
            'totalCount' => $pagination->getTotalCount()
        ];
        $results = ($total = $pagination->getTotalCount())
                    ? $this->limit($perPage)->offset($pagination->offset())->getWith([
                        'pagination' => $this->pagination,
                        'paginator' => $this->paginator($perPage, $page, $options) 
                        ? $this->getModel()->getPaginator($perPage, $page, $options) 
                        : new Paginator($perPage, $page, $options)
                    ])->get()
                    : $this->model->newCollection();

        return $results;
    }

    /**
     * Get paginator
     * 
     * 
     */
    protected function paginator($perPage, $page, array $options)
    {
        return $this->getModel()->getPaginator($perPage, $page, $options);
    }

    /**
     * Get the Pagination instance
     * 
     * @param null
     * 
     * @return Pagination
     */
    public function getPagination()
    {
        return $this->pagination;
    }

    /**
     * Get results with some constraints added
     * 
     * @param array|[] $instances
     * 
     * @return static
     */
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

    /**
     * Query from a view instead of a table
     * 
     * @param string $view
     * 
     * @return static
     */
    public function getFromView($view = null)
    {
        if ($view) {
            $objectCallingView = $view;
        } else {
            if (isset($this->getModel()->view_name)) {
                $objectCallingView = $this->getModel()->view_name;
            } else {
                $objectCallingView = strtolower(Str::deCamelize(class_base($this->getModel())))."_view";
            }
        }
        $this->getModel()->setTable($objectCallingView);
        return $this->getModel();
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