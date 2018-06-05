<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\View;

use Exception;
use Yuga\Views\UI\Site;
use Yuga\Views\Widgets\Form\Form;
use Yuga\Views\Widgets\Html\Html;

class ViewModel extends BaseView
{
    const EXT = '.php';
    
    protected $template;
    protected $contentHtml;
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
     * Create an Onload Event
     */
    public function onLoad()
    {

    }

    /**
     * Create an OnPostBack event
     */
    public function onPostBack()
    {

    }

    /**
     * Calculates template path from given Widget name.
     * @return string
     */
    protected function getTemplatePath()
    {
        $path = array_slice(explode('\\', static::class), 2);
        return ($this->templatePath) ?: path('resources'. DIRECTORY_SEPARATOR .'views'. DIRECTORY_SEPARATOR .'templates'. DIRECTORY_SEPARATOR . join(DIRECTORY_SEPARATOR, $path) . '.php');
    }

    public function validationFor($name, $class = 'help-block')
    {
        if ($validation = $this->getValidation($name)) {
            $span = new Html('span');
            $span->addClass($class);
            $span->append($validation);

            return $span;
        }
        return '<span class="'. $class .'"></span>';
    }
    public function validatedField($field_name, array $options = null, $placeholder = null)
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
            $container->addClass('has-error');
        }

        if ($showLabel) {
            // label 
            $label = $this->form()->label(ucfirst($labelText), $name);
            if ($labelClass != "false" && $labelClass != 'null') {
                $label->addClass($labelClass);
            }
            $container->append($label);
        }

        $input = $this->form()->input($inputType, $name, ($inputType != 'password') ? old($name) : null);
        if ($fieldClass != "false" && $fieldClass != 'null') {
            $input->addClass($fieldClass);
        }
        $input->id($name);
        $input->attr('autofocus');
        if ($placeholder) {
            $input->attr('placeholder', $placeholder);
        }

        

        // create the field container
        $fieldContainer = new Html($fieldParentElem);
        if ($fieldParentClass != "false" && $fieldParentClass != 'null') {
            $fieldContainer->addClass($fieldParentClass);
        }
        $fieldContainer->append($input);
        $fieldContainer->append($this->validationFor($name));

        $container->append($fieldContainer);

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
            $this->getSite()->addMeta(['content' => join(', ', $this->getSite()->getKeywords()), 'name' => 'keywords']);
        }

        if (count($this->getSite()->getHeader()) > 0) {
            $header = $this->getSite()->getHeader();
            $output .= join('', $header);
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

    protected function setTemplate($path, $relative = true)
    {
        $path = str_replace('.', '/', $path);
        $this->template = (($relative === true && trim($path) !== '') ? path('resources'.DIRECTORY_SEPARATOR.'views' . DIRECTORY_SEPARATOR) . $path.self::EXT : '');
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
     * @param string $file
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
     * @param \Yuga\View\ViewModel $viewmodel
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
            throw new Exception($e->getMessage());
            //$this->setError($e->getMessage());
         }

        return '';
    }

    public function getTemplateHtml()
    {

    }

    /**
     * @return string
     */
    public function render()
    {
        $this->bindViewToModel();
        // Trigger onLoad event
        $this->onLoad();

        // Trigger postback event
        if(request()->getMethod() === 'post') {
            $this->onPostBack();
        }

        $this->renderContent();
        $this->renderTemplate();

        return $this->contentHtml;
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
        if ($this->session->exists('errors')) {
            $error = new Html($elem);
            $error->addClass($class);
            $error->append(implode('<br />', $this->errors->getFirst()));
            return $error;
        }
        return ''; 
    }

    public function showSuccessMessage($elem = 'div', $class = 'alert alert-success')
    {
        if ($this->session->exists('success')) {
            $success = new Html($elem);
            $success->addClass($class);
            $success->append($this->session->get('success'));
            return $success;
        }
        return ''; 
    }
}