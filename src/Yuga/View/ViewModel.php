<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\View;

use Closure;
use Exception;
use ReflectionClass;
use Yuga\Views\UI\Site;
use Yuga\Validate\Message;
use Yuga\Models\ElegantModel;
use Yuga\Views\Widgets\Form\Form;
use Yuga\Views\Widgets\Html\Html;

class ViewModel extends BaseView
{
    const EXT = '.php';
    
    protected $template;
    protected $contentHtml;
    public $fieldClasses = [];
    public $handleAjax = true;
    protected $contentTemplate;
    protected $templatePath = null;
    protected $defaultLayout = 'default';

    public function __construct()
    {
        parent::__construct();
        $this->setTemplate($this->defaultLayout);
        $this->setContentTemplate($this->getTemplatePath());
    }

    /**
     * Create an onLoad Event
     */
    public function onLoad()
    {

    }

    /**
     * Calculates template path from given Widget name.
     * @return string
     */
    protected function getTemplatePath()
    {
        $path = array_slice(explode('\\', static::class), 2);
        if ($this->templatePath) {
            return $this->templatePath;
        } else {
            $unAltered = str_replace('ViewModel', '', join(DIRECTORY_SEPARATOR, $path));
            $lower = strtolower($unAltered);
            if (file_exists(path('resources'. DIRECTORY_SEPARATOR .'views'. DIRECTORY_SEPARATOR .'templates'. DIRECTORY_SEPARATOR . $unAltered . '.php'))) {
                return path('resources'. DIRECTORY_SEPARATOR .'views'. DIRECTORY_SEPARATOR .'templates'. DIRECTORY_SEPARATOR . $unAltered . '.php');
            } else {
                return path('resources'. DIRECTORY_SEPARATOR .'views'. DIRECTORY_SEPARATOR .'templates'. DIRECTORY_SEPARATOR . $lower . '.php');
            }
        }
    }

    public function validationFor($name, $class = 'help-block')
    {
        $this->fieldClasses[$name]['error'] = $class;
        if ($validation = $this->getValidation($name)) {
            $span = new Html('span');
            $span->addClass($class);
            $span->append($validation);

            return $span;
        }

        return '<span class="'. $class .'"></span>';
    }
    public function validatedField($field_name, array $options = null, $placeholder = null, $value = null)
    {
        $showLabel = true;
        $fieldOptions = explode(':', $field_name);
        $name = $fieldOptions[0];
        $inputType = 'text';
        $fieldClass = 'form-control';
        if (isset($fieldOptions[1])) {
            $inputType = $fieldOptions[1];
        }
        $labelText = str_replace('_', ' ', $name);
        $labelClass = 'col-md-4 control-label';
        $parentElem = 'div';
        $parentClass = 'form-group';
        $fieldParentElem = 'div';
        $fieldParentClass = 'col-md-6';
        $parentErrorClass = 'has-error';
        if ($options) {
            if (isset($options['label'])) {
                if ($options['label'] == false) {
                    $showLabel = false;
                }

                $labelOptions = $this->processField($options['label']);
                
                if (isset($labelOptions['class'])) {
                    $labelClass = $labelOptions['class'];
                }

                if (isset($labelOptions['text'])) {
                    $labelText = $labelOptions['text'];
                }
            }

            if (isset($options['parent'])) {
                if ($options['parent'] !== false && $options['parent'] !== null) {
                    $parentOptions = $this->processField($options['parent']);
                
                    if (isset($parentOptions['class'])) {
                        $parentClass = $parentOptions['class'];
                    }

                    if (isset($parentOptions['tag'])) {
                        $parentElem = $parentOptions['tag'];
                    }

                    if (isset($parentOptions['error-class'])) {
                        $parentErrorClass = $parentOptions['error-class'];
                    }
                } 
            }

            if (isset($options['field-parent'])) {
                if ($options['field-parent'] !== false && $options['field-parent'] !== null) {
                    $fieldParentOptions = $this->processField($options['field-parent']);
                
                    if (isset($fieldParentOptions['class'])) {
                        $fieldParentClass = $fieldParentOptions['class'];
                    }

                    if (isset($fieldParentOptions['tag'])) {
                        $fieldParentElem = $fieldParentOptions['tag'];
                    }
                } 
            }

            if (isset($options['field'])) {
                if ($options['field'] !== false && $options['field'] !== null) {
                    $fieldOptions = $this->processField($options['field']);
                
                    if (isset($fieldOptions['class'])) {
                        $fieldClass = $fieldOptions['class'];
                    }
                } 
            }
        }

        $container = new Html($parentElem);
        if ($parentClass != "false" && $parentClass != "null") {
            $container->addClass($parentClass);
        }
        if ($this->getValidation($name)) {
            $container->addClass($parentErrorClass);
        }

        if ($showLabel) {
            // label 
            $label = $this->form()->label(ucfirst($labelText), $name);
            if ($labelClass != "false" && $labelClass != 'null') {
                $label->addClass($labelClass);
            }
            $container->append($label);
        }

        $input = $this->form()->input($inputType, $name, ($inputType != 'password') ? $value ?: old($name) : null);
        if ($fieldClass != "false" && $fieldClass != 'null') {
            $input->addClass($fieldClass);
        }
        $input->id($name);
        $input->attr('autofocus');
        if ($placeholder) {
            $input->attr('placeholder', $placeholder);
        }

        // create the field container
        if (isset($options['field-parent'])) {
            if ($options['field-parent'] !== false && $options['field-parent'] !== null) {
                $fieldContainer = new Html($fieldParentElem);
                if ($fieldParentClass != "false" && $fieldParentClass != 'null') {
                    $fieldContainer->addClass($fieldParentClass);
                }
                $fieldContainer->append($input);
                $fieldContainer->append($this->validationFor($name));

                $container->append($fieldContainer);
            } else {
                $container->append($input); 
                $container->append($this->validationFor($name)); 
            }
        } else {
            $fieldContainer = new Html($fieldParentElem);
            if ($fieldParentClass != "false" && $fieldParentClass != 'null') {
                $fieldContainer->addClass($fieldParentClass);
            }
            $fieldContainer->append($input);
            $fieldContainer->append($this->validationFor($name));

            $container->append($fieldContainer);
        }
        $this->fieldClasses[$name]['input'] = $input->getClass();
        $this->fieldClasses[$name]['parent'] = $container->getClass();
        $this->fieldClasses[$name]['parent_error'] = $parentErrorClass;
        
        return $container;
    }

    protected function processField($options = null)
    {
        $fieldOptions = [];
        foreach (explode('|', $options) as $option) {
            $opt = explode(':', $option);
            $fieldOptions[$opt[0]] = $opt[1];
        }

        return $fieldOptions;
    }

    /**
     * @return string
     */
    public function printMeta()
    {

        $output = '';

        if ($this->getSite()->getDescription() !== null) {
            $this->getSite()->addMeta(['content' => $this->getSite()->getDescription(), 'name' => 'description']);
        }

        if (count($this->getSite()->getKeywords()) > 0) {
            $this->getSite()->addMeta(['content' => implode(', ', $this->getSite()->getKeywords()), 'name' => 'keywords']);
        }

        if (count($this->getSite()->getHeader()) > 0) {
            $header = $this->getSite()->getHeader();
            $output .= implode('', $header);
        }

        return $output;
    }

    public function printCss($section = Site::SECTION_DEFAULT)
    {
        $output = '';
        foreach ((array)$this->getSite()->getCss($section) as $css) {
            $output .= (new Html('link'))
                ->setClosingType(Html::CLOSE_TYPE_NONE)
                ->attr('href', $css)
                ->attr('rel', 'stylesheet');
        }

        return $output;
    }

    public function printJs($section = Site::SECTION_DEFAULT)
    {
        $output = '';

        foreach ((array)$this->getSite()->getJs($section) as $js) {
            $output .= (new Html('script'))->attr('src', $js);
        }

        return $output;
    }

    protected function getTemplate()
    {
        return $this->template;
    }

    protected function setTemplatePath($path)
    {
        $this->templatePath = path($path);
        return $this;
    }  

    protected function setTemplate($path, $relative = true)
    {
        $path = str_replace('.', '/', $path);
        $this->template = (($relative === true && trim($path) !== '') ? path('resources'.DIRECTORY_SEPARATOR.'views' . DIRECTORY_SEPARATOR) . $path.self::EXT : '');
    }

    protected function setLayout($path, $relative = true)
    {
        return $this->setTemplate($path, $relative);
    }

    protected function setContentTemplate($template)
    {
        $this->contentTemplate = $template;
    }

    protected function getContentTemplate()
    {
        return $this->contentTemplate;
    }

    protected function setContentHtml($html)
    {
        $this->contentHtml = $html;
    }

    protected function getContentHtml()
    {
        return $this->contentHtml;
    }

    protected function renderBody()
    {
        return $this->getContentHtml();
    }

    /**
     * Creates form element
     * 
     * @author Hamidouh Semix <semix.hamidouh@gmail.com>
     * 
     * @param null
     * 
     * @return Form
     */
    public function form()
    {
        return new Form();
    }

    /**
     * Include snippet from the content/snippet directory
     * by filling the path to the desired snippet.
     *
     * @author Hamidouh Semix <semix.hamidouh@gmail.com>
     * 
     * @param string $file
     * 
     * @return null
     */
    public function display($file, array $data = null)
    {
        $file = str_replace('.', DIRECTORY_SEPARATOR, $file);
        if ($data) {
            extract($data);
        }
        require path('resources'.DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR. $file) . self::EXT;
    }

    /**
     * Include viewmodel on page.
     * 
     * @author Hamidouh Semix <semix.hamidouh@gmail.com>
     * 
     * @param \Yuga\View\ViewModel $viewmodel
     * 
     * @return ViewModel $viewmodel
     */
    public function viewModel(ViewModel $viewmodel)
    {
        if ($viewmodel->getTemplate() === $this->getTemplate()) {
            $viewmodel->setTemplate(null);
        }
        return $viewmodel;
    }

    public function __toString()
    {
        try {
            return $this->render();
         } catch (Exception $e) {
            try {
                throw new Exception($e->getMessage());
            } catch (Exception $e) {
                echo $e->getMessage();
            }
            
            //$this->setError($e->getMessage());
         }

        return '';
    }

    public function getTemplateHtml()
    {

    }

    /**
     * Render the view to the user so they can interact with the app
     * 
     * @author Hamidouh Semix <semix.hamidouh@gmail.com>
     * 
     * @param null
     * 
     * @return string
     */
    public function render()
    {
        $this->bindViewToModel();

        // Trigger onLoad event
        $this->onLoad();

        // Trigger events
        if (in_array($this->request->getMethod(), ['post', 'get'])) {
            $event = ucfirst($this->request->getMethod());
        } else {
            $event = 'Post'.ucfirst($this->request->getMethod());
        }
        $this->handlePostRequest($event);

        // Render html
        $this->renderContent();

        $this->renderTemplate();
        $this->session->put('field-classes', $this->fieldClasses);

        return $this->contentHtml;
    }

    /**
     * Emulate all methods passed by the form but through the post and create appropriate onPost[method] events 
     * e.g. onPostSave
     * 
     * @author Hamidouh Semix <semix.hamidouh@gmail.com>
     * 
     * @param string $event
     * 
     * @return null
     */
    protected function handlePostRequest($event)
    {
        if (method_exists($this, 'on' . $event)) {
            $loaded = $this->loadDependencies(static::class, 'on' . $event);
            
            if ($formModel = $this->getModel('form')) {
                $model = $formModel;
            } else {
                $model = new ElegantModel;
                if (!is_null($this->table)) {
                    $model->setTable($this->table);
                }
            }

            $fields = $this->request->getInput()->all();
            unset($fields['_token']); 
            if (isset($fields['_method'])) {
                unset($fields['_method']);
            }

            if (count($this->ignoreFields) > 0) {
                foreach ($this->ignoreFields as $unset)
                    unset($fields[$unset]);
            }
            
            
            if (!$model->exists) {
                $model->setRawAttributes($fields);
            } else {
                foreach ($fields as $key => $value) {
                    $model->{$key} = $value;
                }
            }

            $model = $this->validateModel($model);

            if (count($this->modelFields) > 0) {
                foreach ($model->getRawAttributes() as $field => $value) {
                    if (in_array($field, array_keys($this->modelFields))) {
                        unset($model->$field);
                        $model->{$this->modelFields[$field]} = $value;
                    }
                }
            }
            $loaded[] = $model;
            call_user_func_array([$this, 'on' . $event], $loaded);
        }
    }

    /**
     * Provide Automatic validation for the model that is bound to the form
     * 
     * @author Hamidouh Semix <semix.hamidouh@gmail.com>
     * 
     * @param Model $model
     * 
     * @return Model $model
     */
    protected function validateModel($model)
    {
        $validate = function ($viewmodel) {
            $validateFields = [];
            if (method_exists($this, 'validate')) {
                $validateFields = $this->validate();
            } else {
                foreach ($this->exists ? $this->getDirty() : $this->getRawAttributes() as $field => $value) {
                    $validateFields[$field] = 'required';
                }
            }
            
            if ($validateFields) {
                if ($viewmodel->request->isAjax()) {
                    $validation = $viewmodel->validate($validateFields);
                    if ($validation instanceof Message) {
                        if ($viewmodel->handleAjax) {
                            $classes = $viewmodel->session->get('field-classes');
                            foreach ($validation->getMessages() as $field => $messages) {
                                if (isset($classes[$field])) {
                                    $input = jq('[name=' . $field . ']');
                                    if (isset($classes[$field]['parent'])) {
                                        $parentClass = $classes[$field]['parent'][0];
                                        $parentError = $classes[$field]['parent_error'];
                                    } else {
                                        $parentError = 'has-error';
                                        $parentClass = 'form-group';
                                    }
                                    $parent = $input->parents('.'.$parentClass);
                                    $parent->addClass($parentError);
                                    $parent->find('.'.$classes[$field]['error'])->text($messages[0]);  
                                }
                            }
                            jq('.error-container')->addClass($classes['error_summary'])->html(implode('<br />', $validation->getFirst()))->run();
                        } else {
                            return $viewmodel->response->json([
                                'errors' => implode('<br />', $validation->getFirst()),
                                'app_status' => false
                            ]);
                        }
                    }
                } else {
                    $viewmodel->validate($validateFields);
                }
            }
        };

        $validateModel = Closure::bind($validate, $model, $model);
        $validateModel($this);
        return $model;
    }

    /**
     * Load All dependencies by their type and instanciate each of them
     * 
     * @author Hamidouh Semix <semix.hamidouh@gmail.com>
     * 
     * @param string $class
     * 
     * @param string $method
     * 
     * @return array $dependecies
     */
    protected function loadDependencies($class, $method = 'onPost')
    {
        $reflection = new ReflectionClass($class);

        $reflectionMethod = $reflection->getMethod($method);
        $reflectionParameters = $reflectionMethod->getParameters();

        $dependecies = [];
        foreach ($reflectionParameters as $parameter) {
            if (!is_null($parameter->getClass())) {
                $dependency = $parameter->getClass()->name;
                if($binding = $this->isSingleton($dependency)) {
                    $dependecies[] = $binding;
                } else {
                    $dependecies[] = $this->app->resolve($dependency);
                }
            } 
        }
        
        return $dependecies;
    }

    /**
     * Check if a class has been registered as singleton by the container
     * 
     * @param string $class
     * 
     * @return boolean
     */
    protected function isSingleton($class)
    {
        foreach(array_values($this->app->getSingletons()) as $instance){
            if(get_class($instance) == $class){
                return $instance;
            }
        }
        return false;
    }


    protected function renderContent()
    {
        if ($this->contentHtml === null && $this->contentTemplate !== null) {
            ob_start();
            extract($this->data);
            include $this->contentTemplate;
            $this->contentHtml = ob_get_contents();
            ob_end_clean();
        }
    }

    protected function renderTemplate()
    {
        if ($this->template !== '') {
            ob_start();
            include $this->template;
            $this->contentHtml = ob_get_contents();
            ob_end_clean();
        }
    }

    public function showErrors($elem = 'div', $class = 'alert alert-danger')
    {
        $this->fieldClasses['error_summary'] = $class;
        if ($this->session->exists('errors')) {
            $error = new Html($elem);
            $error->addClass($class);
            $error->append(implode('<br />', $this->errors->getFirst()));
            return $error;
        }
        return '<div class="error-container"></div>'; 
    }

    public function showSuccessMessage($name = 'success', $elem = 'div', $class = 'alert alert-success')
    {
        if ($this->session->exists($name)) {
            $success = new Html($elem);
            $success->addClass($class);
            $success->append($this->session->flash($name));
            return $success;
        }
        return ''; 
    }

    public function showValidationSummary($element = 'div', $class = 'alert alert-danger')
    {
        return $this->showErrors($element, $class);
    }
}