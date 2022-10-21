<?php

namespace Yuga\Views\Widgets\Html;

use Yuga\Http\Middleware\BaseCsrfVerifier;

class HtmlForm extends Html
{
    const ENCTYPE_APPLICATION_URLENCODED = 'application/x-www-form-urlencoded';
    const ENCTYPE_FORM_DATA = 'multipart/form-data';
    const ENCTYPE_TEXT_PLAIN = 'text/plain';

    const METHOD_POST = 'post';
    const METHOD_GET = 'get';

    public $onSubmit = [];
    public $buttons = [];
    public $controls = [];
    public $submittedBy;

    protected $except = ['_token'];
    public $make = false;

    public function __construct($name = null, $method = self::METHOD_POST, $action = null, $encoding = self::ENCTYPE_APPLICATION_URLENCODED, $construct = true)
    {
        parent::__construct('form');

        //$this->closingType = static::CLOSE_TYPE_NONE;
        if ($name) {
            $this->name($name);
        }

        $this->enctype($encoding);
        $this->method($method);
        $this->action(($action === null) ? route() : $action);
        $this->make = $construct;

        // Add csrf token
        if (strtolower($method) !== 'get') {
            $this->addInnerHtml("\n".(new HtmlInput('hidden', BaseCsrfVerifier::POST_KEY, csrf_token()))->setClosingType(static::CLOSE_TYPE_SHORT)."\n");
        }
    }

    public function name($name)
    {
        return $this->addAttribute('name', $name);
    }

    public function method($method)
    {
        if ($method !== static::METHOD_GET) {
            return $this->addAttribute('method', $method);
        }

        return $this;
    }

    public function enctype($enctype)
    {
        if ($enctype !== static::ENCTYPE_APPLICATION_URLENCODED) {
            return $this->addAttribute('enctype', $enctype);
        }

        return $this;
    }

    public function fileUpload()
    {
        return $this->enctype(static::ENCTYPE_FORM_DATA);
    }

    public function action($action)
    {
        return $this->addAttribute('action', $action);
    }

    protected function getFormAction()
    {
        return $this->getAttribute('action');
    }

    public function input($name, $type = 'text', $value = null, $saveValue = true)
    {
        if ($saveValue && ($value === null && input()->get($name) !== null || request()->getMethod() !== 'get')) {
            $value = input()->get($name);
        }

        return (new HtmlInput($type, $name, $value))->id($name);
    }

    /**
     * Add a submit button.
     *
     * @param string      $name
     * @param string      $type
     * @param string|null $value
     * @param bool        $saveValue
     */
    public function submitButton($name, $type = 'submit', $value = null, $saveValue = true)
    {
        if ($saveValue && ($value === null && input()->get($name) !== null || request()->getMethod() !== 'get')) {
            $value = input()->get($name);
        }

        return (new HtmlSubmitButton($type, $name, $value))->id($name);
    }

    /**
     * Creates new label.
     *
     * @param string|null $inner
     * @param string|null $for
     *
     * @return \Yuga\Views\Widgets\Html\Html
     */
    public function label($inner, $for = null)
    {
        $label = new Html('label');

        if ($inner !== null) {
            $label->addInnerHtml($inner);
        }

        if ($for !== null) {
            $label->attr('for', $for);
        }

        return $label;
    }

    public function textarea($name, $rows = 3, $cols = 22, $value = null, $saveValue = true)
    {
        if ($saveValue === true && ($value === false && input()->get($name) !== null || request()->getMethod() !== 'get')) {
            $value = input()->get($name);
        }

        return (new HtmlTextarea($name, $rows, $cols, $value))->id($name);
    }

    public function submit($name, $value)
    {
        $this->except[] = $name;

        return $this->submitButton($name, 'submit', $value);
    }

    /**
     * Renders form to string.
     *
     * @param can throw exceptions? (hidden parameter)
     */
    public function __toString(): string
    {
        try {
            if (request()->getMethod() == 'post') {
                // do onClick events and onSubmit events
                $this->fireFormEvents($this);
            }
            if ($this->make === true) {
                // $this->buildOutput();
                $this->buildFormWithTable();
            }
            // else {
            //     $this->buildOutput('div');
            // }
            return parent::__toString();
        } catch (\Throwable $e) {
            trigger_error('Exception in '.__METHOD__."(): {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}", E_USER_ERROR);
        }
    }

    public function fireFormEvents(HtmlForm $form)
    {
        foreach ($this->onSubmit as $onSubmitSuccess) {
            $onSubmitSuccess($form, request()->except($this->except));
        }
    }

    /**
     * Add an input to a form.
     *
     * @param string $name
     * @param string $label
     * @param string $type
     *
     * @return Html $control
     */
    public function addInput($name, $label, $type)
    {
        $label = $this->label($label, $name);
        $control = $this->input($name, $type);

        $this->controls[$name] = compact('label', 'control');

        return $control;
    }

    /**
     * Add a text input to this form
     * Alias to addInput.
     */
    public function addTextInput($name, $label)
    {
        return $this->addInput($name, $label, 'text');
    }

    /**
     * Add a textarea to a form.
     *
     * @param string $name
     * @param string $label
     * @param string $type
     *
     * @return Html $control
     */
    public function addTextarea($name, $label)
    {
        $label = $this->label($label, $name);
        $control = $this->textarea($name);
        $this->controls[$name] = compact('label', 'control');

        return $control;
    }

    /**
     * Add submit buttons.
     *
     * @param string $name
     * @param string $label
     * @param string $type
     *
     * @return Html $control
     */
    public function addSubmit($name, $label)
    {
        $control = $this->submit($name, $label);
        $this->controls[$name] = compact('control');
        $this->buttons[] = $name;

        return $control;
    }

    /**
     * Creates new HTML Select element.
     *
     * @param string             $name
     * @param array|Dataset|null $data
     * @param string|null        $value
     * @param bool               $saveValue
     *
     * @throws \InvalidArgumentException
     *
     * @return \Yuga\Views\Widgets\Html\HtmlSelect
     */
    public function selectStart($name, $data = null, $value = null, $saveValue = true)
    {
        $element = new HtmlSelect($name);
        if ($data !== null) {
            if ($data instanceof Collection) {
                foreach ($data->getData() as $item) {
                    $val = isset($item['value']) ? $item['value'] : $item['name'];
                    $selected = (input()->get($name) !== null && (string) input()->get($name) === (string) $val || input()->exists($name) === false && (string) $value === (string) $val || (isset($item['selected']) && $item['selected']) || $saveValue === false && (string) $value === (string) $val);
                    $element->addOption(new HtmlSelectOption($val, $item['name'], $selected));
                }
            } elseif (is_array($data) === true) {
                foreach ($data as $val => $key) {
                    $selected = (input()->get($name) !== null && (string) input()->get($name) === (string) $val || input()->exists($name) === false && (string) $value === (string) $val || $saveValue === false && (string) $value === (string) $val);
                    $element->addOption(new HtmlSelectOption($val, $key, $selected));
                }
            } else {
                throw new \InvalidArgumentException('Data must be either instance of Collection or array.');
            }
        }

        return $element;
    }

    public function addSelect($name, $label, $data = null, $value = null)
    {
        $label = $this->label($label, $name);
        $control = $this->selectStart($name, $data, $value);
        $this->controls[$name] = compact('label', 'control');

        return $control;
    }

    public function buildOutput($formParent = 'table')
    {
        if ($formParent == 'table') {
            return $this->buildFormWithTable();
        } else {
            return $this->buildFormWithParent($formParent);
        }
    }

    protected function buildFormWithParent(?string $formParent = null)
    {
        foreach ($this->controls as $name => $controlObject) {
            $this->append($controlObject['control']);
        }

        return $this;
    }

    protected function buildFormWithTable()
    {
        $layout = '';

        $layout = new Html('table');
        $layout->addClass('table');
        $buttonsContainer = new Html('tr');
        $buttonsPadding = new Html('td');
        $buttonControls = [];
        foreach ($this->controls as $name => $controlObject) {
            if (!in_array($name, $this->buttons)) {
                $controlContainer = new Html('tr');
                $labelContainer = new Html('th');
                $label = $controlObject['label'];
                $control = $controlObject['control'];
                $labelContainer->append($label);

                $inputContainer = new Html('td');
                $inputContainer->append($control);
                $controlContainer->append($labelContainer);
                $controlContainer->append($inputContainer);
                $layout->append($controlContainer);
            } else {
                $buttonControls[] = $controlObject['control'];
            }
        }
        $buttonsContainer->append($buttonsPadding.' '.(new Html('td'))->append(implode(' ', $buttonControls)));
        $layout->append($buttonsContainer);

        $this->append($layout);

        return $this;
    }

    /**
     * Returns form's action.
     *
     * @return mixed
     */
    public function getAction()
    {
        return $this->getFormAction()[0];
    }
}
