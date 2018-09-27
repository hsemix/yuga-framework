<?php
namespace Yuga\Database\Elegant;

use Exception;
use ArrayAccess;
use Carbon\Carbon;
use ReflectionClass;
use JsonSerializable;
use Yuga\Events\Event;
use Yuga\Support\Inflect;
use Yuga\Support\FileLocator;
use Yuga\Database\Connection\Connection;
use Yuga\Database\Elegant\Association\HasOne;
use Yuga\Database\Elegant\Association\HasMany;
use Yuga\Database\Elegant\Association\BelongsTo;
use Yuga\Database\Elegant\Association\Mergeable;
use Yuga\Database\Elegant\Association\BelongsToMany;
use Yuga\Database\Elegant\Association\MergeableMany;
use Yuga\Database\Elegant\Exceptions\ModelException;

abstract class Model implements ArrayAccess, JsonSerializable
{
    public $exists = false;
    protected $table_name;
    protected $view_name;
    protected $jsonInclude = [];
    private $original = [];
    private $attributes = [];
    public $timestamps = true;
    protected static $container;
    protected static $connection;
    protected static $dispatcher;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    protected static $primaryKey = 'id';
    protected $deleteKey = 'deleted_at';
    protected $hidden = [];
    protected $fillable = [];
    protected static $massAssign = false;
    public $relations = [];
    public $returnWithRelations = false;
    protected $bootable = [];
    protected $pagination;
    protected $queryable;
    public function __construct(array $options = [])
    {
        $this->events();
        $this->syncAttributes();
        $this->fillModelWith($options);	
    }

    public function events()
    {
        // start all events;
    }
    public function setEventDispatcher(Event $event)
    {
        static::$dispatcher = $event;
    }
    public static function setConnection(Connection $connection)
    {
        static::$connection = $connection;
        static::$container = $connection->getContainer();
    }

    public function getContainer()
    {
        return static::$container;
    }

    public function getConnection()
    {
        return static::$connection;
    }

    /**
     * Begin querying the model on a given connection.
     *
     * @param  Connection  $connection
     * @return \Yuga\Database\Elegant\Builder
     */
    public static function on(Connection $connection = null)
    {
        $instance = new static;

        $instance->setConnection($connection);

        return $instance->newElegantQuery();
    }


    public function fillModelWith(array $attributes)
    {
        $this->fillModelWithSingle($attributes);
        return $this;
    }

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
                    throw new Exception('Need to have fillables for mass assignment or set protected static $massAssign to true in your model');
                }
                
            } 
        }
        return $this;
    }

    /**
     * Sync the original attributes with the current.
     *
     * @return $this
     */
    public function syncAttributes()
    {
        $this->original = $this->attributes;
        return $this;
    }

    /**
	 * get a variable and make an object point to it
	 */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }
	/**
	 * Set a variable and make an object point to it
	 */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     *
     * @param  string  $key
     * @return bool
     */
    public function __isset($key)
    {
        return ! is_null($this->getAttribute($key));
    }

    /**
     * Unset an attribute on the model.
     *
     * @param  string  $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key], $this->relations[$key]);
    }

    /**
	 * Build a find by any field in the database
	 *
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
    
    public function getPagination()
    {
        return $this->pagination;
    }
	/**
	 * Make the object act like an array when at access time
	 *
	 */
    public function offsetSet($offset, $value) 
    {
        if (is_null($offset)) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }

    public function offsetExists($offset) 
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetUnset($offset) 
    {
        unset($this->attributes[$offset]);
    }

    public function offsetGet($offset) 
    {
        return isset($this->attributes[$offset]) ? $this->attributes[$offset] : null;
    }

    public function toJson($options = null)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    public function __toString()
    {
        return $this->toJson();
    }

    public function jsonSerialize()
    {
        $attributes = (array) $this->attributes;
        if (!empty($this->jsonInclude)) {
            foreach ($this->jsonInclude as $field) {
                $attributes[$field] = $this->getAttribute($field);
            }
        }

        //if (count($this->bootable) > 0) {
           // unset($this->pagination);
        //}
        
        $attributes = array_map(function($attribute) {
            if (!is_array($attribute)) {
                if (!is_object($attribute)) {
                    $json_attribute = json_decode($attribute);
                    if (json_last_error() == JSON_ERROR_NONE)
                        return $json_attribute;
                }
            }
            return $attribute;
        }, $attributes);
        return $this->removeHiddenFields($attributes);
    }

    public function toArray()
    {
        return $this->jsonSerialize();
    }

	
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Get an attribute from the model.
     *
     * @param  string  $key
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

    public function getTable()
    {
        $calledClass =  class_base(static::class);  
		if (isset($this->table_name)) {
            return $this->table_name;
        }
        return Inflect::pluralize(strtolower($calledClass));
    }

    public static function getFromView($constraints = null)
    {
        $objectCalling = get_called_class();
        $model = new static;
        if ($constraints) {
            $objectCallingView = $constraints;
        } else {
            if (isset($model->view_name)) {
                $objectCallingView = $model->view_name;
            } else {
                $objectCallingView = strtolower(class_base($objectCalling))."_view";
            }
        }
        $model->setTable($objectCallingView);
        return $model;
    }

    public function setTable($table)
    {
        $this->table_name = $table;
        return $this;
    }

    public function getPrimaryKey()
    {
        if (isset(static::$primaryKey)) {
            return static::$primaryKey;
        }
        return self::$primaryKey;
    }

    public function getDeleteKey()
    {
        return $this->deleteKey;
    }
    
    public function newInstance($attributes = [], $exists = false)
    {
        $model = new static((array) $attributes);
        $model->exists = $exists;
        return $model;
    }

    public function newElegantQuery()
    {
		return $this->newElegantMainQueryBuilder($this);
    }
    
    protected function newElegantMainQueryBuilder(Model $model = null)
    {
        return $this->queryable?:new Builder($this->getConnection(), $model);
    }

    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }

    public static function makeModels($items, array $bootable = null)
    { 
        // make models from plain arrays got from db
        $instance = new static;
        
        $items = array_map(function ($item) use ($instance, $bootable) {
            return $instance->newFromQuery($item, $bootable);
        }, $items);
        return $instance->newCollection($items);
    }

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

    public function newFromQuery($attributes = [], array $bootable = null)
    {
        $model = $this->newInstance([], true);
        $model->setRawAttributes((array) $attributes, true);
        $model->attributes = (array)$attributes;
        if (!is_null($bootable)) {
            $this->bootable = $bootable;
            foreach ($bootable as $start => $class) {
                $model->$start = $class;
            }
        }
        return $model;
    }

    public function setQuery(Builder $query)
    {
        $this->queryable = $query;
    }

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
     * @param array $options 
     * @return Model $saved
	 */

    public function save(array $options = [])
    {
        $query = $this->newElegantQuery();
        if ($this->dispatchModelEvent('saving', [$query]) === false) {
            return false;
        }
        if($this->exists){
            $saved = $this->performUpdate($query, $options);
        }else{
            $saved = $this->performInsert($query, $options);
        }
        $this->dispatchModelEvent('saved', [$query]);
        return $saved;
    }

    /**
	 * create and save an object
	 *
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
        //return true;
        return $this;
    }

    public function getUpdatedAtColumn()
    {
        return static::UPDATED_AT;
    }

    public function getCreatedAtColumn()
    {
        return static::CREATED_AT;
    }

    protected function createTimestamps()
    {
        return $this->newElegantQuery()->dates($this->getUpdatedAtColumn(), $this->getCreatedAtColumn());
    }

    /**
	 * update an object
	 *
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
        return true;
        //return $this;
    }

    protected function setKeysForSaveQuery(Builder $query)
    {
        $query->where($this->getPrimaryKey(), '=', $this->getKeyForSaveQuery());

        return $query;
    }

    protected function getKeyForSaveQuery()
    {
        return $this->getAttribute($this->getPrimaryKey());
    }

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

    protected function newTimestamp()
    {
        return Carbon::now()->toDateTimeString();
    }

    public function setCreatedAt($value)
    {
        $this->{static::CREATED_AT} = $value;
        return $this;
    }

    public function setUpdatedAt($value)
    {
        $this->{static::UPDATED_AT} = $value;
        return $this;
    }

    public function updateTimestamps()
    {
        if (!$this->timestamps) {
            return false;
        }
        $this->setTimestamps();

        return $this->save();
    }

    public static function create(array $attributes = [])
    {
        static::$massAssign = true;
        $self = new static();
        $model = $self->make($attributes);
		$model->save();
        return $model;
    }

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
     * @return array
     */
    public function getDirty()
    {
        $dirty = [];

        foreach ($this->attributes as $key => $value) {
            if (! array_key_exists($key, $this->original)) {
                $dirty[$key] = $value;
            } elseif ($value !== $this->original[$key] && ! $this->originalIsNumericallyEquivalent($key)) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    /**
     * Remove an Object either softly or permanently
     */

    public function delete($permanent = false)
    {
        if (is_null($this->getPrimaryKey())){
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

    public function performDeleteOnModel($permanent = false)
    {
        return $this->setKeysForSaveQuery($this->newElegantQuery())->delete($permanent);
    }

    /**
     * Determine if the new and old values for a given key are numerically equivalent.
     *
     * @param  string  $key
     * @return bool
     */
    protected function originalIsNumericallyEquivalent($key)
    {
        $current = $this->attributes[$key];

        $original = $this->original[$key];

        return is_numeric($current) && is_numeric($original) && strcmp((string) $current, (string) $original) === 0;
    }
	
    public function make(array $options)
    {
        $object = new static($options);
        
		return $object;
    }

    public function currentInstance()
    {
        return $this;
    }

    public function __clone()
    {
        $this->queryable = clone $this->newElegantQuery();
        $this->queryable->setModel($this);
    }

    /**
     * return the appropriate namespace of the model
     *
     * @param  string  $related
     * @return \Namespace\$related
     */

    protected function buildNamespace()
    {
        return $this->getFinder()->getNamespaceFromClass(static::class);
    }

    protected function getFinder()
    {
        return $this->getContainer()->resolve(FileLocator::class);
    }
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

    protected function buildRelatedNamespace($related)
    {
        $relatedNamespaces = explode("\\", $related);
        if (count($relatedNamespaces) == 1) {
            $related = $this->buildNamespace().'\\'.$related;
        }
        return $related;
    }

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
     * @return string
     */
    public function getForeignKey()
    {
        return strtolower(class_base($this).'_id');
    }

    public function joinTables($class) 
    {
        $base = strtolower(class_base($this));

        $class = strtolower(class_base($class));

        $models = [$class, $base];
        sort($models);
        return strtolower(implode('_', $models));
    }
    
    /**
	 * make the joins of sql queries one to many
	 *
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

    public function hasOne($class, $foreignKey = null, $otherKey = null)
    {
		$foreignKey = $foreignKey?$foreignKey:$this->getForeignKey();
		$otherKey = $otherKey?$otherKey:$this->getPrimaryKey();
		$class = $this->returnAppropriateNamespace($class);
		$instance = new $class;
		return new HasOne($instance->newElegantQuery(), $this, $instance->getTable().'.'.$foreignKey, $otherKey);
    }
    
    /**
	 * make the joins of sql queries many to many
	 *
	 */

    public function belongsToMany($class, $table_name = null, $first_table_primary_key = null, $second_table_primary_key=null)
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
    
    public function getMergeableClass()
    {
		return static::class;
    }
    
    /**
	 * returns one object from the caller class
	 *
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
     * Register all events from the model and dispatch them later
     */
    public static function creating($callback, $priority = 0)
    {
        return static::registerModelEvent('creating', $callback, $priority);
    }
    public static function created($callback, $priority = 0)
    {
        return static::registerModelEvent('created', $callback, $priority);
    }

    public static function saving($callback, $priority = 0)
    {
        return static::registerModelEvent('saving', $callback, $priority);
    }
    public static function saved($callback, $priority = 0)
    {
        return static::registerModelEvent('saved', $callback, $priority);
    }

    public static function updating($callback, $priority = 0)
    {
        return static::registerModelEvent('updating', $callback, $priority);
    }
    public static function updated($callback, $priority = 0)
    {
        return static::registerModelEvent('updated', $callback, $priority);
    }

    public static function deleting($callback, $priority = 0)
    {
        return static::registerModelEvent('deleting', $callback, $priority);
    }
    public static function deleted($callback, $priority = 0)
    {
        return static::registerModelEvent('deleted', $callback, $priority);
    }

    public static function selecting($callback, $priority = 0)
    {
        return static::registerModelEvent('selecting', $callback, $priority);
    }

    public static function selected($callback, $priority = 0)
    {
        return static::registerModelEvent('selected', $callback, $priority);
    }

    protected static function registerModelEvent($event, $callback, $priority = 0)
    {
        if (isset(static::$dispatcher)) {
            $name = static::class;
            static::$dispatcher->attach("elegant.{$event}: {$name}", $callback, $priority);
        }
    }

    /**
     * Dispatch all model events
     */
    public function dispatchModelEvent($event, $args = [])
    {
        $this->setEventDispatcher(new Event);
        $eventMethod = $event;

        if (!isset(static::$dispatcher)) {
            return true;
        }

        $event = "elegant.{$event}: ".static::class;

        return static::$dispatcher->dispatch($event, $args);
    }

    
    public function setRelation($relation, $value)
    {
        $this->relations[$relation] = $value;
        return $this;
    }
}