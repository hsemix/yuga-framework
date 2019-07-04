<?php
namespace Yuga\Database\Elegant;

use Closure;
use Exception;
use ArrayAccess;
use Carbon\Carbon;
use ReflectionClass;
use JsonSerializable;
use Yuga\Support\Str;
use Yuga\Events\Event;
use Yuga\Support\Inflect;
use Yuga\Support\FileLocator;
use Yuga\Pagination\Paginator;
use Yuga\Pagination\Pagination;
use Yuga\Database\Connection\Connection;
use Yuga\Database\Elegant\Association\HasOne;
use Yuga\Database\Elegant\Association\HasMany;
use Yuga\Database\Elegant\Association\BelongsTo;
use Yuga\Database\Elegant\Association\Mergeable;
use Yuga\Database\Elegant\Association\BelongsToMany;
use Yuga\Database\Elegant\Association\MergeableMany;
use Yuga\Database\Elegant\Exceptions\ModelException;
use Yuga\Interfaces\Database\Elegant\Association\Association as Relation;

abstract class Model implements ArrayAccess, JsonSerializable
{
    protected $queryable;
    protected $view_name;
    protected $paginator;
    protected $table_name;
    protected $pagination;
    public $bootable = [];
    public $exists = false;
    private $original = [];
    protected $hidden = [];
    public $relations = [];
    protected $perPage = 15;
    protected $fillable = [];
    private $attributes = [];
    public $timestamps = true;
    protected $jsonInclude = [];
    protected static $container;
    protected static $dispatcher;
    protected static $connection;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    protected $virtualRelations = [];
    protected $deleteKey = 'deleted_at';
    protected static $primaryKey = 'id';
    public $returnWithRelations = false;
    protected static $massAssign = false;
    
    /**
     * Make a new Model instance
     * 
     * @param array|[] $options
     * 
     * @return void
     */
    public function __construct(array $options = [])
    {
        $this->syncAttributes();
        $this->fillModelWith($options);	
    }

    /**
     * Start or boot all events
     * 
     * @param null
     * 
     * @return void
     */
    public function events()
    {
        $events = ['creating', 'created', 'saving', 'saved', 'updating', 'updated', 'deleting', 'deleted', 'selecting', 'selected'];
        foreach ($events as $event) {
            if (method_exists($this, 'on'.ucfirst($event))) {
                static::$event([$this, 'on'.ucfirst($event)]);
            }
        }
    }

    /**
     * Get PerPage items
     * 
     * @param null
     * 
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * Set the model Event Dispatcher
     * 
     * @param Event $event
     * 
     * @return void
     */
    public function setEventDispatcher(Event $event)
    {
        static::$dispatcher = $event;
    }

    /**
     * Set a connection on which you want to query this model
     * 
     * @param Connection $connection
     * 
     * @return void
     */
    public static function setConnection(Connection $connection)
    {
        static::$connection = $connection;
        static::$container = $connection->getContainer();
    }

    /**
     * Get the Yuga Container (Resolver)
     * 
     * @param null
     * 
     * @return \Yuga\Container\Container
     */
    public function getContainer()
    {
        return static::$container;
    }

    /**
     * Get the connection on which to query this model
     * 
     * @param null
     * 
     * @return \Yuga\Database\Connection\Connection
     */
    public function getConnection()
    {
        return static::$connection;
    }

    /**
     * Begin querying the model on a given connection.
     *
     * @param  Connection  $connection
     * 
     * @return \Yuga\Database\Elegant\Builder
     */
    public static function on(Connection $connection = null)
    {
        $instance = new static;

        $instance->setConnection($connection);

        return $instance->newElegantQuery();
    }

    /**
     * Fill a model with attributes
     * 
     * @param array $attributes
     * 
     * @return static
     */
    public function fillModelWith(array $attributes)
    {
        $this->fillModelWithSingle($attributes);
        return $this;
    }

    /**
     * Fill a model with a single array of attributes
     * 
     * @param array $attributes
     * 
     * @return static
     */
    protected function fillModelWithSingle(array $attributes)
    {
        foreach ($attributes as $key => $value) {
            if (!empty($this->fillable)){
                if (in_array($key, $this->fillable)) {
                    $this->setAttribute($key, $value);
                }
            } else {
                if (static::$massAssign) {
                    $this->setAttribute($key, $value);
                } else {
                    throw new Exception('Need to have fillables for mass assignment or set protected static $massAssign to true in your model (' . static::class . ')');
                }
                
            } 
        }
        return $this;
    }

    /**
     * Sync the original attributes with the current.
     * 
     * @param null
     *
     * @return static
     */
    public function syncAttributes()
    {
        $this->original = $this->attributes;
        return $this;
    }

    /**
	 * get a variable and make an object point to it
     * 
     * @param null
     * 
     * @return void
	 */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Return all attributes
     * 
     * @param null
     * 
     * @return array
     */
    public function getRawAttributes()
    {
        return $this->attributes;
    }

	/**
	 * Set a variable and make an object point to it
     * 
     * @param string $key
     * @param mixed $value
     * 
     * @return void
	 */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string  $key
     * 
     * @return bool
     */
    public function __isset($key)
    {
        return !is_null($this->getAttribute($key));
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string  $key
     * 
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key], $this->relations[$key]);
    }

    /**
	 * Build a find by any field in the database
	 *
     * @param string $method
     * @param array $parameters
     * 
     * @return Builder
	 */
    public function __call($method, $parameters)
    {
        $query = $this->newElegantQuery();
        if (preg_match('/^findBy(.+)$/', $method, $matches)) {
			return $this->where(strtolower($matches[1]), $parameters[0]);
        }
        if (method_exists($query, $method))
            return call_user_func_array([$query, $method], $parameters);
        return null;
    }

    /**
	 * Query the model statically and return a query builder
	 *
     * @param string $method
     * @param array $parameters
     * 
     * @return Builder
	 */
    public static function __callStatic($method, $args) 
    {
        $instance = new static;
        $query = $instance->newElegantQuery();
		if (preg_match('/^findBy(.+)$/', $method, $matches)) {
			return $instance->where(strtolower($matches[1]), $args[0]);
        }
        if (method_exists($query, $method))
            return call_user_func_array([$instance, $method], $args);
        return null;
    }
    
    /**
     * Get pagination for paginating results of this model
     * 
     * @param null
     * 
     * @return \Yuga\Pagination\Pagination
     */
    public function getPagination()
    {
        return $this->pagination;
    }

	/**
	 * Make the object act like an array when at access time
	 *
     * @param $offset
     * @param $value
     * 
     * @return void
	 */
    public function offsetSet($offset, $value) 
    {
        if (is_null($offset)) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }

    /**
     * Determine whether an attribute exists on this model
     * 
     * @param $offset
     * 
     * @return boolean
     */
    public function offsetExists($offset) 
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * Unset an attribute if it doesn't exist
     * 
     * @param $offset
     * 
     * @return void
     */
    public function offsetUnset($offset) 
    {
        unset($this->attributes[$offset]);
    }

    /**
     * Get the value of an attribute from an array given its key
     * 
     * @param string $offset
     * 
     * @return mixed
     */
    public function offsetGet($offset) 
    {
        return isset($this->attributes[$offset]) ? $this->attributes[$offset] : null;
    }

    /**
     * Change the model to a json string
     * 
     * @param array|null $options
     * 
     * @return string
     */
    public function toJson($options = null)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Change the model to a string
     * 
     * @param null
     * 
     * @return void
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * Implement a json serializer
     * 
     * @param null
     * 
     * @return array
     */
    public function jsonSerialize()
    {
        $attributes = (array) $this->attributes;
        if (!empty($this->jsonInclude)) {
            foreach ($this->jsonInclude as $field) {
                $attributes[$field] = $this->getAttribute($field);
            }
        }

        if (!empty($this->relations)) {
            foreach ($this->relations as $field => $relations) {
                $attributes[$field] = $relations->toArray();
            }
        }
    
        $attributes = array_map(function($attribute) {
            if (!is_array($attribute)) {
                if (!is_object($attribute)) {
                    $json_attribute = json_decode($attribute, true);
                    if (json_last_error() == JSON_ERROR_NONE)
                        return $json_attribute;
                } else {
                    return (array)$attribute;
                }
            }
            return $attribute;
        }, $attributes);
        return $this->removeHiddenFields($attributes);
    }

    /**
     * Get all relations of this model
     * 
     * @param null
     * 
     * @return array
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * Get all bootable fields of this model
     * 
     * @param null
     * 
     * @return array
     */
    public function getBootable()
    {
        return $this->processBootableMethod;
    }

    /**
     * Change an object to an array
     * 
     * @param null
     * 
     * @return mixed
     */
    public function toArray()
    {
        return $this->jsonSerialize();
    }

	/**
     * Set a model attribute
     * 
     * @param string $key
     * @param mixed
     * 
     * @return static
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
     * 
     * @return mixed
     */
    public function getAttribute($key)
    {
		$new_instance_class = $this->newInstance([], true);
        $class = new ReflectionClass($new_instance_class);
        
		if ($class->hasMethod('hasMany') && $class->hasMethod($key) && Inflect::pluralize($key) == $key) {
			$this->attributes[$key] = $this->$key()->get();
		} elseif ($class->hasMethod('belongsTo') && $class->hasMethod($key)) {
			$this->attributes[$key] = $this->$key()->first();
		} elseif ($class->hasMethod('hasOne') && $class->hasMethod($key)) {
			$this->attributes[$key] = $this->$key()->first();
		}
        return @$this->attributes[$key];
    }

    /**
     * Get the model table
     * 
     * @param null
     * 
     * @return string
     */
    public function getTable()
    {
        $calledClass =  class_base(static::class);  
		if (isset($this->table_name)) {
            return $this->table_name;
        }
        $calledClass = Str::deCamelize($calledClass);
        return Inflect::pluralize(strtolower($calledClass));
    }

    /**
     * Set a table correponding to this model for database queries
     * 
     * @param string $table
     * 
     * @return static
     */
    public function setTable($table)
    {
        $this->table_name = $table;
        return $this;
    }

    /**
     * Get the primary key of the table corresponding to this Model
     * 
     * @param null
     * 
     * @return string
     */
    public function getPrimaryKey()
    {
        if (isset(static::$primaryKey)) {
            return static::$primaryKey;
        }
        return self::$primaryKey;
    }

    /**
     * Get the delete key of the table corresponding to this model
     * 
     * @param null
     * 
     * @return string
     */
    public function getDeleteKey()
    {
        return $this->deleteKey;
    }
    
    /**
     * Make a new Instance of the Model class
     * 
     * @param array|[] $attributes
     * @param boolean $exists
     * 
     * @return static
     */
    public function newInstance($attributes = [], $exists = false)
    {
        $model = new static((array) $attributes);
        $model->exists = $exists;
        return $model;
    }

    /**
     * Get a model QueryBuilder instance from the model
     * 
     * @param null
     * 
     * @return Builder
     */
    public function newElegantQuery()
    {
		return $this->newElegantMainQueryBuilder($this);
    }
    
    /**
     * Get a model QueryBuilder instance from the model
     * 
     * @param null
     * 
     * @return Builder
     */
    protected function newElegantMainQueryBuilder(Model $model = null)
    {
        return $this->queryable?:new Builder($this->getConnection(), $model);
    }

    /**
     * Make a collection of models from plain arrays got from a database table
     * 
     * @param array|[] $models
     * 
     * @return Collection
     */
    public function newCollection(array $models = [])
    {
        if (count($models) > 0) {
            if ($models[0]->paginator) {
                $model = $models[0];
                $paginator = $model->paginator;
                $paginator->setPagination($model->pagination);
                $models = array_map(function ($model) {
                    unset($model->paginator, $model->pagination);
                    return $model;
                }, $models);
                return $paginator->setItems($models);
            } else {
                return new Collection($models);
            }
        }
    }

    /**
     * Get paginator for paginating results of this model
     * 
     * @param int $perPage
     * @param int $page
     * @param array $options
     * 
     * @return \Yuga\Pagination\Paginator
     */
    public function getPaginator($perPage, $page, array $options)
    {
        
    }

    /**
     * Set a model pagination for later
     * 
     * @param Pagination $pagination
     * @return Model
     */
    public function setPagination(Pagination $pagination)
    {
        $this->pagination = $pagination;
        return $this;
    }

    /**
     * Make models from plain arrays got from database tables
     * 
     * @param array $items
     * @param array|null $bootable
     * 
     * @return Collection
     */
    public function makeModels($items, array $bootable = null)
    { 
        // make models from plain arrays got from db
        $instance = new static;
        
        $items = array_map(function ($item) use ($instance, $bootable, $items) {
            return $instance->newFromQuery($item, $bootable, $items);
        }, $items);
        return $instance->newCollection($items);
    }

    /**
     * Remove given fields from the model attributes when casted to array or json
     * 
     * @param array|[] $attributes
     * 
     * @return array $items
     */
    protected function removeHiddenFields(array $attributes = [])
    {
        $attributeKeys = array_keys($attributes);
        $removedHiddenFields = array_diff($attributeKeys, $this->hidden);    
        $items = [];
        foreach ($attributes as $key => $value) {
            if (in_array($key, $removedHiddenFields)) {
                $items[$key] = $value;
            }
        }
        return $items;
    }

    /**
     * Create a new Elegant model from query
     * 
     * @param array|[] $attributes
     * @param array|null $bootable
     * 
     * @return Model $model
     */
    public function newFromQuery($attributes = [], array $bootable = null, array $items = [])
    {
        $model = $this->newInstance([], true);
        $model->setRawAttributes((array) $attributes, true);
        $model->attributes = (array)$attributes;
        if (!is_null($bootable)) {
            $this->bootable = $bootable;
            foreach ($bootable as $start => $class) {
                // $model->$start = $class;
                if ($start != 'pagination' && $start != 'paginator')
                    $this->invokeBootable($start, $model, $items);
                else 
                    $model->$start = $class;
            }
        }
        return $model;
    }

    /**
     * Invoke funtions or return strings or arrays that functions return
     * 
     * @param string $name
     * @param Model $model
     * @param array $items
     * 
     * @return void
     */
    protected function invokeBootable($name, $model, array $items)
    {
        $with = $this->bootable[$name];

        if (is_numeric($name) === true) {
            $name = $with;
        }

        if ($with instanceof Closure) {
            $result = $this->processBootableClosure($with, $model, $name, $items);
        } else {
            $result = $this->processBootableMethod($with, $model, $name, $items);
        }

        if (is_array($result)) {
            if (array_key_exists('field', $result) && array_key_exists('results', $result)) {
                $name = $result['field'];
                $result = $result['results'];
            }
        }
    
        if ($result instanceof Collection) {
            $model->relations[$name] = $result;
        } elseif($result instanceof Model) {
            $model->relations[$name] = $result;
        } else {
            $model->{$name} = $result;
            $model->bootable[$name] = $result;
        }
    }

    /**
     * Process methods included in the bootable array
     * 
     * @param string $with
     * @param Model $model
     * @param string $name
     * 
     * @return mixed $result
     */
    protected function processBootableMethod($with, $model, $name, $items)
    {
        if (!is_array($with) && !is_object($with)) {
            if (!$this->isNested($name, explode('.', $with)[0])) {
                if (method_exists($model, $name)) {

                    $result = $model->$name();

                    if ($result instanceof Relation) {
                        $result = $result->get();
                    }
                } else {
                    $result = $with;
                }
            } else {
                $result = $this->processNestedWith($with, $model, $name, $items);
            }
        } else {
            $result = $with;
        }
        
        return $result;
    }

    /**
     * Process closures included in the bootable array
     * 
     * @param string $with
     * @param Model $model
     * @param string $name
     * 
     * @return mixed
     */
    protected function processBootableClosure($with, $model, $name)
    {
        $result = call_user_func($with, $model->$name());

        if (is_null($result)) {
            $result = $model->$name;
        } else if (is_object($result)) {
            $result = $result->get();
        }

        return $result;
    }

    /**
     * Process nested relations passed in the with method
     * 
     * @param string $with
     * @param Model $model
     * @param string $name
     * 
     * @return mixed
     */
    protected function processNestedWith($with, $model, $name, $items)
    {
        $names = explode('.', $name);
        if (method_exists($model, $names[0])) {
            $method = $names[0];
            $result = $model->$method();
            if ($result instanceof Relation) {
                unset($names[0]);
                $result = $result->with(implode('.', $names))->get();
            } else {
                if (in_array($method, $this->virtualRelations)) {
                    if ($result instanceof Model) {
                        unset($names[0]);
                        $result = $result->with(implode('.', $names))->get();
                    } else if ($result instanceof Collection) {
                        unset($names[0]);
                        $result = isset($result[0]) ? $result[0]->with(implode('.', $names))->get() : $result;
                    }
                }
            }
            $result =  ['field' => $method, 'results' => $result];
        } else {
            $result = null;
        }

        return $result;
    }

    /**
     * Determine whether a relation is nested
     * 
     * @param string $relation
     * 
     * @return boolean
     */
    protected function isNested($name, $relation)
    {
        $dots = Str::contains($name, '.');

        return $dots && Str::startsWith($name, $relation.'.');
    }

    /**
     * Set the query to be used by this model
     * 
     * @param Builder $query
     * 
     * @return void
     */
    public function setQuery(Builder $query)
    {
        $this->queryable = $query;
    }

    /**
     * Set Raw attributes of the model
     * 
     * @param array $attributes
     * @param boolean $sync
     * 
     * @return static
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        $this->attributes = $attributes;
        if ($sync) {
            $this->syncAttributes();
        }
        return $this;
    }

    /**
	 * decides whether to update or create an object
     * 
     * @param array $options 
     * 
     * @return Model $saved
	 */
    public function save(array $options = [])
    {
        $query = $this->newElegantQuery();
        if ($this->dispatchModelEvent('saving', [$query]) === false) {
            return false;
        }
        if ($this->exists) {
            $saved = $this->performUpdate($query, $options);
        } else {
            $saved = $this->performInsert($query, $options);
        }
        $this->dispatchModelEvent('saved', [$query]);
        return $saved;
    }

    /**
	 * create and save an object
	 * 
     * @param Builder $query
     * @param array|[] $options
     * 
     * @return static
	 */
    protected function performInsert(Builder $query, array $options = [])
    {    
        if ($this->dispatchModelEvent('creating', [$query]) === false) {
            return false;
        }
        if ($this->timestamps) {
            $this->setTimestamps();
            $this->createTimestamps();
        }
        $attributes = $this->attributes;
        if (count($options) !== 0) {
            /* Only save valid columns */
            $options = array_filter($options, function ($key) {
                return (!in_array($key, $this->attributes, true) === true);
            }, ARRAY_FILTER_USE_KEY);

            $attributes = array_merge($attributes, $options);
        }
        
        $query->create($attributes);
        $this->exists = true;
        $this->{$this->getPrimaryKey()} = $query->getModel()->{$this->getPrimaryKey()};
        $this->dispatchModelEvent('created', [$query]);
        return $this;
    }

    /**
     * Get the updated_at field name of the model
     * 
     * @param null
     * 
     * @return string
     */
    public function getUpdatedAtColumn()
    {
        return static::UPDATED_AT;
    }

    /**
     * Get the created_at field name of the model
     * 
     * @param null
     * 
     * @return string
     */
    public function getCreatedAtColumn()
    {
        return static::CREATED_AT;
    }

    /**
     * Created updated_at and created_at fields in the databae table corresponding to this model if they don't already exist
     * 
     * @param null
     * 
     * @return mixed
     */
    protected function createTimestamps()
    {
        return $this->newElegantQuery()->dates($this->getUpdatedAtColumn(), $this->getCreatedAtColumn());
    }

    /**
	 * Check the model for dirty fields
	 *
     * @param array|[] $options
     * 
     * @return array
     */
    protected function checkDirtyOptions(array $options = [])
    {
        if (count($options) !== 0) {
            /* Only save valid columns */
            $options = array_filter($options, function ($key) {
                return (!in_array($key, $this->attributes, true) === true);
            }, ARRAY_FILTER_USE_KEY);

            $this->attributes = array_merge($this->attributes, $options);
        }
        return $this->attributes;
    }

    /**
     * Update a record in the database table corresponding to this model
     * 
     * @param Builder $query
     * @param array|[] $options
     * 
     * @return boolean
     */
    protected function performUpdate(Builder $query, array $options = [])
    {
        if (count($options) !== 0) {
            $this->checkDirtyOptions($options);
        }
        
        if(!$this->getDirty()) {
            return;
        }
        if ($this->dispatchModelEvent('updating', [$query]) === false) {
            return false;
        }
        if($this->timestamps) {
            $this->setTimestamps();
            $this->createTimestamps();
        }
        $this->setKeysForSaveQuery($query)->update($this->getDirty());
        $this->dispatchModelEvent('updated', [$query]);
        // return true;
        return $this;
    }

    /**
     * Set primary keys of the updated model to the current query
     * 
     * @param Builder $query
     * 
     * @return Builder $query
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $query->where($this->getPrimaryKey(), '=', $this->getKeyForSaveQuery());

        return $query;
    }

    /**
     * Get the primary key value of the Model that has just been saved
     * 
     * @param null
     * 
     * @return mixed
     */
    protected function getKeyForSaveQuery()
    {
        return $this->getAttribute($this->getPrimaryKey());
    }

    /**
     * Set timestamps to new values
     * 
     * @param null
     * 
     * @return void
     */
    protected function setTimestamps()
    {
        $time = $this->newTimestamp();

        if (!$this->isDirty(static::UPDATED_AT)) {
            $this->setUpdatedAt($time);
        }

        if (!$this->exists && !$this->isDirty(static::CREATED_AT)) {
            $this->setCreatedAt($time);
        }
    }

    /**
     * Get an time stamp string
     * 
     * @param null
     * 
     * @return string
     */
    protected function newTimestamp()
    {
        $carbon = \Yuga\Carbon\Carbon::class;
        if (class_exists(Carbon::class)) {
            $carbon = Carbon::class;
        }
        return $carbon::now()->toDateTimeString();
    }

    /**
     * Set a created_at value
     * 
     * @param string $value
     * 
     * @return static
     */
    public function setCreatedAt($value)
    {
        $this->{static::CREATED_AT} = $value;
        return $this;
    }

    /**
     * Set an updated_at value
     * 
     * @param string $value
     * 
     * @return static
     */
    public function setUpdatedAt($value)
    {
        $this->{static::UPDATED_AT} = $value;
        return $this;
    }

    /**
     * Update the timestamps fields of the Model database table
     * 
     * @param null
     * 
     * @return static
     */
    public function updateTimestamps()
    {
        if (!$this->timestamps) {
            return false;
        }
        $this->setTimestamps();

        return $this->save();
    }

    /**
     * Create a record in a database table from a plain array
     * 
     * @param array|[] $attributes
     * 
     * @return Model
     */
    public static function create(array $attributes = [])
    {
        static::$massAssign = true;
        $self = new static();
        $model = $self->make($attributes);
		$model->save();
        return $model;
    }

    /**
     * Determine whether the fields have been edited
     * 
     * @param array|null $attributes
     * 
     * @return boolean
     */
    public function isDirty($attributes = null)
    {
        $dirty = $this->getDirty();
		
        if (is_null($attributes)) {
            return count($dirty) > 0;
        }

        if (! is_array($attributes)) {
            $attributes = func_get_args();
        }

        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $dirty)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the attributes that have been changed since last sync.
     *
     * @param null
     * 
     * @return array $dirty
     */
    public function getDirty()
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->bootable)) {
                if (!array_key_exists($key, $this->original)) {
                    $dirty[$key] = $value;
                } elseif ($value !== $this->original[$key] && !$this->originalIsNumericallyEquivalent($key)) {
                    $dirty[$key] = $value;
                }
            }
        }

        return $dirty;
    }

    /**
     * Remove an Object either softly or permanently
     * 
     * @param boolean|false $permanent
     * 
     * @return static
     */
    public function delete($permanent = false)
    {
        if (is_null($this->getPrimaryKey())) {
            throw new ModelException('No primary key defined on model.');
        }

        if ($this->exists){
            $this->exists = false;
            $query = $this->newElegantQuery();
            if ($this->dispatchModelEvent('deleting', [$query]) === false) {
                return false;
            }
            $deleted =  $this->performDeleteOnModel($permanent);

            $this->dispatchModelEvent('deleted', [$query]);
            return $deleted;
        }
    }

    /**
     * Delete a Model
     * 
     * @param boolean|false $permanent
     * 
     * @return static
     */
    public function performDeleteOnModel($permanent = false)
    {
        return $this->setKeysForSaveQuery($this->newElegantQuery())->delete($permanent);
    }

    /**
     * Determine if the new and old values for a given key are numerically equivalent.
     *
     * @param  string  $key
     * 
     * @return bool
     */
    protected function originalIsNumericallyEquivalent($key)
    {
        $current = $this->attributes[$key];

        $original = $this->original[$key];

        return is_numeric($current) && is_numeric($original) && strcmp((string) $current, (string) $original) === 0;
    }
    
    /**
     * Make a Model instance from a plain array
     * 
     * @param array $options
     * 
     * @return Model
     */
    public function make(array $options)
    {
        $object = new static($options);
        
		return $object;
    }

    /**
     * Return the current instance of the model
     * 
     * @param null
     * 
     * @return static
     */
    public function currentInstance()
    {
        return $this;
    }

    /**
     * Customize the clone behaviour of the model
     * 
     * @param null
     * 
     * @return void
     */
    public function __clone()
    {
        $this->queryable = clone $this->newElegantQuery();
        $this->queryable->setModel($this);
    }

    /**
     * return the appropriate namespace of the model
     *
     * @param null
     * 
     * @return mixed
     */
    protected function buildNamespace()
    {
        return $this->getFinder()->getNamespaceFromClass(static::class);
    }

    /**
     * Return a new instance of the File locator
     * 
     * @param null
     * 
     * @return FileLocator
     */
    protected function getFinder()
    {
        return $this->getContainer()->resolve(FileLocator::class);
    }

    /**
     * Return an appropriate namespace of a relation
     * 
     * @param string $related
     * 
     * @return string $related
     */
    protected function returnAppropriateNamespace($related)
    {
        $classes = explode("\\", static::class);
        if (count($classes) > 1) {
            $namespaces = $this->getFinder()->getClassesOfNamespace($this->buildNamespace());
            foreach ($namespaces as $namespace) {
                if (strstr($namespace,  $related)) {
                    $related = $this->getFromDeclaredNamespaces($namespace, $related);
                } else {
                    $related = $this->buildRelatedNamespace($related);
                }
            }
        }
        return $related;
    }

    /**
     * Make a namespace of a relation
     * 
     * @param string $related
     * 
     * @return string $related
     */
    protected function buildRelatedNamespace($related)
    {
        $relatedNamespaces = explode("\\", $related);
        if (count($relatedNamespaces) == 1) {
            $related = $this->buildNamespace().'\\'.$related;
        }
        return $related;
    }

    /**
     * Get model namespace from already declared namespaces
     * 
     * @param string $namespace
     * @param string $related
     * 
     * @return string $related
     */
    protected function getFromDeclaredNamespaces($namespace, $related)
    {
        $className = explode("\\", $namespace);
        if ($className[count($className)-1] == $related) {
            $related = $namespace;
        }
        return $related;
    }

    /**
     * Get the default foreign key name for the model.
     * 
     * @param null
     *
     * @return string
     */
    public function getForeignKey()
    {
        return strtolower(class_base($this).'_id');
    }

    /**
     * Join tables from classes
     * 
     * @param string $class
     * 
     * @return string
     */
    public function joinTables($class) 
    {
        $base = strtolower(class_base($this));

        $class = strtolower(class_base($class));

        $models = [$class, $base];
        sort($models);
        return strtolower(implode('_', $models));
    }
    
    /**
     * Define a one-to-many relationship.
     *
     * @param  string $class
     * @param  string|null $foreignKey
     * @param  string|null $otherKey
     * 
     * @return HasMany
     */
    public function hasMany($class, $foreignKey = null, $otherKey = null)
    {
		$foreignKey = $foreignKey?$foreignKey:$this->getForeignKey();
		$otherKey = $otherKey?$otherKey:$this->getPrimaryKey();
        $class = $this->returnAppropriateNamespace($class);
        
        
		$instance = new $class;
		return new HasMany($instance->newElegantQuery(), $this, $instance->getTable().'.'.$foreignKey, $otherKey);
    }
    
    /**
	 * make the joins of sql queries one to one
	 *
     * @param string $class
     * @param string|null $foreignKey
     * @param string|null $otherKey
     * @param string|null $relation
     * 
     * @return BelongsTo
	 */
    public function belongsTo($class, $foreignKey = null, $otherKey = null, $relation = null)
    {
        if (is_null($relation)) {
            list($current, $caller) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);

            $relation = $caller['function'];
        }

        if (is_null($foreignKey)) {
            $foreignKey = $relation.'_id';
        }
        $class = $this->returnAppropriateNamespace($class);
        $class = new $class;

        $query = $class->newElegantQuery();
        $otherKey = $otherKey?$otherKey:$class->getPrimaryKey();

        return new BelongsTo($query, $this, $foreignKey, $otherKey, $class);
    }

    /**
     * Define a one-to-one relationship.
     *
     * @param string $class
     * @param string|null $foreignKey
     * @param string|null $otherKey
     * 
     * @return HasOne
     */
    public function hasOne($class, $foreignKey = null, $otherKey = null)
    {
		$foreignKey = $foreignKey?$foreignKey:$this->getForeignKey();
		$otherKey = $otherKey?$otherKey:$this->getPrimaryKey();
		$class = $this->returnAppropriateNamespace($class);
		$instance = new $class;
		return new HasOne($instance->newElegantQuery(), $this, $instance->getTable().'.'.$foreignKey, $otherKey);
    }
    
    /**
     * Define a many-to-many relationship.
     *
     * @param string $class
     * @param string|null $table_name
     * @param string|null $first_table_primary_key
     * @param string|null $second_table_primary_key
     * @param string|null $parentKey
     * @param string|null $relatedKey
     * @param string|null $relation
     * 
     * @return BelongsToMany
     */
    public function belongsToMany($class, $table_name = null, $first_table_primary_key = null, $second_table_primary_key = null, $parent_key = null, $related_key = null, $relation = null)
    {
		$first_table_primary_key = $first_table_primary_key ?: $this->getForeignKey();
        $class = $this->returnAppropriateNamespace($class);
        $instance = new $class;

        $second_table_primary_key = ($second_table_primary_key)?$second_table_primary_key : $instance->getForeignKey();
		
        if (is_null($table_name)) {
            $table_name = $this->joinTables($class);
        }
		
        $query = $instance->newElegantQuery();
		
        return new BelongsToMany($query, $this, $table_name, $first_table_primary_key, $second_table_primary_key);
    }
    
    /**
	 * make the joins of sql queries one to many of diffent types of objects in one type
     * 
     * @param string $class
     * @param string $mergable_name
     * @param string|null $mergeable_type
     * @param string|null $mergeable_id
     * @param string|null $primaryKey
	 *
     * @return MergeableMany
	 */
    public function mergeableMany($class, $mergeable_name, $mergeable_type = null, $mergeable_id = null, $primaryKey = null)
    {
		$class = $this->returnAppropriateNamespace($class);

		$instance = new $class;

        list($mergeable_type, $mergeable_id) = $this->getMergeStrings($mergeable_name, $mergeable_type, $mergeable_id);
		$table = $instance->getTable();

        $primaryKey = $primaryKey?$primaryKey:$this->getPrimaryKey();

        return new MergeableMany($instance->newElegantQuery(), $this, $table.'.'.$mergeable_type, $table.'.'.$mergeable_id, $primaryKey);
		
    }
    
    /**
     * Get merged strings
     * 
     * @param string $name
     * @param string|null $type
     * @param string|null $id
     * 
     * @return array
     */
    protected function getMergeStrings($name, $type = null, $id = null)
    {
		if (!$type) {
			$type = $name."_type";
		}
		if (!$id) {
			$id = $name."_id";
		}
		return [$type, $id];
    }
    
    /**
     * Get a mergeable class
     * 
     * @param null
     * 
     * @return string
     */
    public function getMergeableClass()
    {
		return static::class;
    }
    
    /**
	 * returns one object from the caller class
	 *
     * @param string|null $mergeable_name
     * @param string|null $mergeable_type
     * @param string|null $mergeable_id
     * 
     * @return Mergeable
	 */
    public function mergeable($mergeable_name = null, $mergeable_type = null, $mergeable_id = null)
    {
		$instance = new static;
		$debug = debug_backtrace();
		
		$string_for_merging = $debug[1]['function'];
        if(!$mergeable_name){
            $mergeable_name = $string_for_merging;
        }

		list($mergeable_type, $mergeable_id) = $this->getMergeStrings($mergeable_name, $mergeable_type, $mergeable_id);
		$class = ucfirst($this->{$mergeable_type});
		$class = $this->returnAppropriateNamespace($class);
		$instance = new $class;	

        return new Mergeable($instance->newElegantQuery(), $this, $mergeable_id, $instance->getPrimaryKey(), $mergeable_type, $instance);
    }
    
    /**
     * Register the creating event to the dispatcher
     * 
     * @param callable $callback
     * @param int|0 $priority
     * 
     * @return Event
     */
    public static function creating($callback, $priority = 0)
    {
        return static::registerModelEvent('creating', $callback, $priority);
    }

    /**
     * Register the created event to the dispatcher
     * 
     * @param callable $callback
     * @param int|0 $priority
     * 
     * @return Event
     */
    public static function created($callback, $priority = 0)
    {
        return static::registerModelEvent('created', $callback, $priority);
    }

    /**
     * Register the saving event to the dispatcher
     * 
     * @param callable $callback
     * @param int|0 $priority
     * 
     * @return Event
     */
    public static function saving($callback, $priority = 0)
    {
        return static::registerModelEvent('saving', $callback, $priority);
    }

    /**
     * Register the saved event to the dispatcher
     * 
     * @param callable $callback
     * @param int|0 $priority
     * 
     * @return Event
     */
    public static function saved($callback, $priority = 0)
    {
        return static::registerModelEvent('saved', $callback, $priority);
    }

    /**
     * Register the updating event to the dispatcher
     * 
     * @param callable $callback
     * @param int|0 $priority
     * 
     * @return Event
     */
    public static function updating($callback, $priority = 0)
    {
        return static::registerModelEvent('updating', $callback, $priority);
    }

    /**
     * Register the updated event to the dispatcher
     * 
     * @param callable $callback
     * @param int|0 $priority
     * 
     * @return Event
     */
    public static function updated($callback, $priority = 0)
    {
        return static::registerModelEvent('updated', $callback, $priority);
    }

    /**
     * Register the deleting event to the dispatcher
     * 
     * @param callable $callback
     * @param int|0 $priority
     * 
     * @return Event
     */
    public static function deleting($callback, $priority = 0)
    {
        return static::registerModelEvent('deleting', $callback, $priority);
    }

    /**
     * Register the deleted event to the dispatcher
     * 
     * @param callable $callback
     * @param int|0 $priority
     * 
     * @return Event
     */
    public static function deleted($callback, $priority = 0)
    {
        return static::registerModelEvent('deleted', $callback, $priority);
    }

    /**
     * Register the selecting event to the dispatcher
     * 
     * @param callable $callback
     * @param int|0 $priority
     * 
     * @return Event
     */
    public static function selecting($callback, $priority = 0)
    {
        return static::registerModelEvent('selecting', $callback, $priority);
    }

    /**
     * Register the selected event to the dispatcher
     * 
     * @param callable $callback
     * @param int|0 $priority
     * 
     * @return Event
     */
    public static function selected($callback, $priority = 0)
    {
        return static::registerModelEvent('selected', $callback, $priority);
    }

    /**
     * Register an event to the dispatcher
     * 
     * @param string $event
     * @param callable $callback
     * @param int|0 $priority
     * 
     * @return void
     */
    protected static function registerModelEvent($event, $callback, $priority = 0)
    {
        if (isset(static::$dispatcher)) {
            $name = static::class;
            static::$dispatcher->attach("elegant.{$event}: {$name}", $callback, $priority);
        }
    }

    /**
     * Dispatch all model events
     * 
     * @param string $event
     * 
     * @return Event
     */
    public function dispatchModelEvent($event, $args = [])
    {
        $this->setEventDispatcher(new Event);
        $this->events();
        $eventMethod = $event;

        if (!isset(static::$dispatcher)) {
            return true;
        }

        $event = "elegant.{$event}: ".static::class;

        return static::$dispatcher->dispatch($event, $args);
    }

    /**
     * Push a relation to the relations array
     * 
     * @param string $relation
     * @param mixed $value
     * 
     * @return static
     */
    public function setRelation($relation, $value)
    {
        $this->relations[$relation] = $value;
        return $this;
    }
}