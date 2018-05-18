<?php
namespace Yuga\Database\Elegant;

use Closure;
use Carbon\Carbon;
use Yuga\Support\Str;
use Yuga\Http\Request;
use Yuga\Pagination\Pagination;
use Yuga\Database\Elegant\Collection;
use Yuga\Database\Connection\Connection;
use Yuga\Database\Migration\Schema\Table;
use Yuga\Database\Query\Builder as QueryBuilder;
use Yuga\Database\Elegant\Association\Association;
use Yuga\Database\Elegant\Exceptions\ModelException;
use Yuga\Database\Elegant\Exceptions\ModelNotFoundException;

class Builder
{
    protected static $instance;
    
    /**
    * @var Model
    */
    protected $model;
    /**
    * @var QueryBuilder
    */
    protected $query;
    protected $results;
    protected $pagination;
    protected $boot = [];

    protected $withTrashed = false;
    protected $onlyTrashed = false;
    protected $returnWithRelations = false;

    public function __construct(Connection $connection, Model $model)
    {
        $this->model = $model;
        $this->query = (new QueryBuilder())->table($this->model->getTable());
        $this->pagination = new Pagination;
        //$this->query->asObject(get_class($this->getModel()));
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
            if($this->withTrashed){
				
			}elseif($this->onlyTrashed){
				$this->query->whereNotNull($this->getModel()->getDeleteKey());
			}else{
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

        //print_r($name);
        //die();
        //$nested = $this->nestedRelations($name);
        
        
        // If there are nested relationships set on the query, we will put those onto
        // the query instances so that they can be handled after this relationship
        // is loaded. In this way they will all trickle down as they are loaded.
        // if (count($nested) > 0) {
        //     foreach ($nested as $nest => $object) {
                
        //         $relation->with($nest);
        //         //$relation->getChild()->$nest;
        //     }
            
        // }

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

    public function where($key, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            if ($operator instanceof Closure) {
                echo "Yes";
                die();
            }
            $value = $operator;
            $operator = '=';
        }

        $this->query->where($key, $operator, $value);

        return $this;
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
            $model = $this->model->newFromQuery($item);
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

    public function count()
    {
        return $this->query->count();
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
        //$data = array_merge($this->model->getRows(), $this->getValidData($data));

        if (count($data) === 0) {
            throw new ModelException('There are no valid columns found to update.');
        }

        $id = $this->query->insert($data);

        if ($id) {

            //$this->model->mergeRows($data);
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

    public function join($table, $key, $operator = null, $value = null, $type = 'inner')
    {
        $this->query->join($table, $key, $operator, $value, $type);

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

    public function subQuery(Model $model, $alias = null)
    {
        return $this->query->subQuery($model->getQuery(), $alias);
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
            return $this->query->getQuery()->getRawSql();
        return $this->query->getQuery()->getSql();
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
        return (new Table($table))->columnExists($field);
    }

    public function createTableField($table, $field)
    {
        $table = new Table($table);
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
        //$this->model->relations = $results;
		//$this->model->returnWithRelations = true;
        return $results;
    }

    public function paginate($limit = 10, array $options = null)
    {
        $request = new Request;
        $url = explode('?', $request->getUrl());
        $page = 1;
        if (count($url) > 1) {
            $page = (int) $request->get('page') ? : 1;
            if ($options) {
                if (array_key_exists('url', $options)) {
                    $page = (int) $request->get($options['url']) ? : 1;
                }
            }
        }
        // $this->pagination->setPerPage($limit);
        // $this->pagination->setCurrentPage($page);
        // $this->pagination->setTotalCount($this->count());
        // $this->getModel()->pagination = $this->pagination;
        // return $this->limit($limit)->offset($this->pagination->offset())->get();
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
        $this->boot = $instances;
        return $this;
    }


    // public function __call($method, $parameters)
    // {
    //     return call_user_func_array([new Collection([], $this), $method], $parameters);
    // }


}