<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\View;

use Exception;
use ArrayAccess;
use Yuga\Session\Session;
use Yuga\Support\Inflect;
use Yuga\Validate\Message;
use Yuga\Models\ElegantModel;
use Yuga\Http\Input\InputItem;
use Yuga\Database\Elegant\Model;
use Yuga\Database\Elegant\Collection;
use Yuga\Views\Widgets\Form\FormMessage;
use Yuga\Shared\Controller as SharedController;

class BaseView implements ArrayAccess
{
    use SharedController;
    
    protected $errors;
    protected $message;
    protected $data = [];
    protected $models = [];
    protected $model = null;
    protected $table = null;
    protected $ignoreFields = [];

    public function __construct()
    {
        $this->init();
        if ($this->session->exists('errors')) {
            $this->errors = $this->session->get('errors');
        } else {
            $this->errors = new Message;
        }
        $this->message = $this->errors;
    }

    public function offsetSet($offset, $value) 
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    public function offsetExists($offset) 
    {
        return isset($this->data[$offset]);
    }

    public function offsetUnset($offset) 
    {
        unset($this->data[$offset]);
    }

    public function offsetGet($offset) 
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    public function __set($name, $value) 
    {
        $this->data[$name] = $value;
    }

    public function __get($name) 
    {
        return $this->data[$name];
    }

    /**
    * TO DO: handle automatic validation later
    */

    /**
     * Get the site instance as use it
     */

    public function getSite()
    {
        return app()->site;
    }

    /**
     * Determine whether the request is ajax or not
     * 
     * @param null
     * 
     * @return \boolean
     */

    public function isAjaxRequest()
    {
        return (request()->getHeader('http-x-requested-with') !== null && strtolower(request()->getHeader('http-x-requested-with')) === 'xmlhttprequest');
    }

    /**
     * Append some text to the current Site Title
     * 
     * @param \string $title
     * @param \string $separator
     * 
     * @return null
     */

    protected function appendSiteTitle($title, $separator = '-')
    {
        $separator = ($separator === null) ? '' : ' ' . $separator . ' ';
        app()->site->setTitle(app()->site->getTitle() . $separator . $title);
    }


    /**
     * Prepend some text to the current Site Title
     * 
     * @param \string $title
     * @param \string $separator
     * 
     * @return null
     */

    protected function prependSiteTitle($title, $separator = ' - ')
    {
        app()->site->setTitle($title . $separator . app()->site->getTitle());
    }

    /**
     * Determine whether the route was defined with the form method i.e. Route::form('/test')
     * 
     * @param null
     * 
     * @return \boolean
     */
    public function isPostBack()
    {
        return (bool)(request()->getMethod() !== 'get');
    }

    protected function validate($rules = [])
    {
        $fields = $this->request->getInput()->all();
        unset($fields['_token']);
        $validation = $this->validate->check($this->request->getInput()->all(), $rules);
        if ($validation->failed()) {
            if ($this->request->isAjax()) {
                return $validation->errors();
            } else {
                $this->session->put('errors', $validation->errors());
                $this->request->addOld();
                return $this->response->refresh();
            } 
        }
        $this->session->delete('old-data');
        return $validation->getValidated();
    }

    protected function getValidation($name)
    {
        if ($this->errors->has($name)) {
            return $this->errors->first($name);
        }

        return null;
    }

    public function setModel($models)
    {
        if ($models instanceof Model) {
            $this->model = $this['model'] = $models;
        }

        if ($models instanceof Collection) {
            $this->models = $this['models'] = $this->processCollectionModels($models);
        }

        if (is_array($models)) {
            $this->models = $this['models'] = $this->processArrayModels($models);
        }
        
        return $this;
    }

    protected function processCollectionModels(Collection $models)
    {
        $key = Inflect::pluralize(strtolower(class_base($models[0])));
        $this->models[$key] = $models;
        return $this->models;
    }

    protected function processArrayModels(array $models)
    {
        foreach ($models as $key => $model) {
            if (!is_string($key)) {
                if ($model instanceof Collection) {
                    $key = Inflect::pluralize(strtolower(class_base($model[0])));
                } elseif ($model instanceof Model) {
                    $key = strtolower(class_base($model));
                } else {
                    throw new Exception("Acceptable model types are instance of Yuga\Database\Elegant\Model and Yuga\Database\Elegant\Collection", 1);
                }
            }
            $this->models[$key] = $model;
        }
        return $this->models;
    }

    protected function hasStringKeys(array $array) {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }

    public function setTable($table = null)
    {
        $this->table = $table;
        return $this;
    }

    public function bindViewToModel()
    {
        $fields = $this->request->getInput()->all();
        if ($this->model == null) {
            $this->model = new ElegantModel;
            if (!is_null($this->table)) {
                $this->model->setTable($this->table);
            }
        }
        if (request()->getMethod() === 'post') {
            unset($fields['_token']); 
        } 
        if (count($this->ignoreFields) > 0) {
            foreach ($this->ignoreFields as $unset)
                unset($fields[$unset]);
        }
        
        $this->model->setRawAttributes($fields);
        $this['form'] = $fields;
        return $this;
    }

    public function getModel($name = null)
    {
        if ($name) {
            if (isset($this->models[$name])) {
                return $this->models[$name];
            }
        }
        return $this->model;
    }

    public function save()
    {
        return $this->getModel()->save();
    }

}