<?php
namespace Yuga\Views\Widgets\Form;

use Yuga\Boolean;
use Yuga\Collection\Collection;
use Yuga\Views\Widgets\Html\Html;
use Yuga\Views\Widgets\Html\HtmlForm;
use Yuga\Views\Widgets\Html\HtmlInput;
use Yuga\Views\Widgets\Html\HtmlSelect;
use Yuga\Views\Widgets\Html\HtmlCheckbox;
use Yuga\Views\Widgets\Html\HtmlTextarea;
use Yuga\Views\Widgets\Html\HtmlSelectOption;

class Form
{

    protected $htmlForm;
    /**
     * Starts new form
     * @param string $name
     * @param string|null $method
     * @param string|null $action
     * @return \Yuga\Views\Widgets\Html\HtmlForm
     */
    public function start($name, $method = HtmlForm::METHOD_POST, $action = null)
    {
        $form = new HtmlForm($name, $method, $action);
        $form->setClosingType(HtmlForm::CLOSE_TYPE_NONE);
        $form->make = false;
        $this->htmlForm = $form;
        return $this->htmlForm;
    }

    /**
     * Creates new HTML input element
     * @param string $name
     * @param string $type
     * @param string $value
     * @param bool $saveValue
     * @return \Yuga\Views\Widgets\Html\HtmlInput
     */
    public function input($name, $type = 'text', $value = null, $saveValue = true)
    {
        if ($saveValue && ($value === null && input()->exists($name) === true || request()->getMethod() !== 'get')) {
            $value = input()->get($name);
        }

        return new HtmlInput($name, $type, $value);
    }

    /**
     * Create radio element
     *
     * @param string $name
     * @param string $value
     * @param bool $saveValue
     * @return HtmlInput
     */
    public function radio($name, $value, $saveValue = true)
    {
        $element = new HtmlInput($name, 'radio', $value);

        $inputValue = input()->get($name);

        if ($saveValue === true && $inputValue !== null && (string)$inputValue === (string)$value) {
            $element->checked(true);
        }

        return $element;
    }

    /**
     * Creates new checkbox input element
     * @param string $name
     * @param bool $value
     * @param bool $defaultValue
     * @param bool $saveValue
     * @return \Yuga\Views\Widgets\Html\HtmlCheckbox
     */
    public function bool($name, $value = true, $defaultValue = null, $saveValue = true)
    {
        $element = new HtmlCheckbox($name, ($defaultValue === null) ? '1' : $defaultValue);
        if ($saveValue !== false) {
            if ($defaultValue === null) {
                $defaultValue = $value;
            } else {
                $defaultValue = count($_GET) ? null : $defaultValue;
            }
            $checked = Boolean::parse(input()->get($name, $defaultValue));
            if ($checked) {
                $element->checked(true);
            }
        } else {
            if (Boolean::parse($value)) {
                $element->checked(true);
            }
        }

        return $element;
    }

    /**
     * Creates new label
     * @param string|null $inner
     * @param string|null $for
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

    /**
     * Creates new HTML Select element
     * @param string $name
     * @param array|Dataset|null $data
     * @param string|null $value
     * @param bool $saveValue
     * @throws \InvalidArgumentException
     * @return \Yuga\Views\Widgets\Html\HtmlSelect
     */
    public function selectStart($name, $data = null, $value = null, $saveValue = true)
    {
        $element = new HtmlSelect($name);
        if ($data !== null) {
            if ($data instanceof Collection) {

                foreach ($data->getData() as $item) {
                    $val = isset($item['value']) ? $item['value'] : $item['name'];
                    $selected = (input()->get($name) !== null && (string)input()->get($name) === (string)$val || input()->exists($name) === false && (string)$value === (string)$val || (isset($item['selected']) && $item['selected']) || $saveValue === false && (string)$value === (string)$val);
                    $element->addOption(new HtmlSelectOption($val, $item['name'], $selected));
                }

            } elseif (is_array($data) === true) {

                foreach ($data as $val => $key) {
                    $selected = (input()->get($name) !== null && (string)input()->get($name) === (string)$val || input()->exists($name) === false && (string)$value === (string)$val || $saveValue === false && (string)$value === (string)$val);
                    $element->addOption(new HtmlSelectOption($val, $key, $selected));
                }

            } else {
                throw new \InvalidArgumentException('Data must be either instance of Collection or array.');
            }
        }

        return $element;
    }

    /**
     * Creates new textarea
     * @param string $name
     * @param int $rows
     * @param int $cols
     * @param string $value
     * @param bool $saveValue
     * @return \Yuga\Views\Widgets\Html\HtmlTextarea
     */
    public function textarea($name, $rows, $cols, $value = null, $saveValue = true)
    {
        if ($saveValue === true && ($value === false && input()->get($name) !== null || request()->getMethod() !== 'get')) {
            $value = input()->get($name);
        }

        return new HtmlTextarea($name, $rows, $cols, $value);
    }

    /**
     * Creates submit element
     * @param string $name
     * @param string $value
     * @return \Yuga\Views\Widgets\Html\HtmlInput
     */
    public function submit($name, $value)
    {
        return $this->input($name, 'submit', $value);
    }

    /**
     * Create button element
     * @param string $text
     * @param string|null $type
     * @param string|null $name
     * @param string|null $value
     * @return Html
     */
    public function button($text, $type = null, $name = null, $value = null)
    {
        $el = (new Html('button'))->addInnerHtml($text);

        if ($type !== null) {
            $el->addAttribute('type', $type);
        }

        if ($name !== null) {
            $el->addAttribute('name', $name);
        }

        if ($value !== null) {
            $el->addAttribute('value', $value);
        }

        return $el;
    }

    /**
     * Ends open form
     * @return string
     */
    public function end()
    {
        return "\n</form>";
    }

    public function __call($method, $args) 
    {
        return call_user_func_array([$this->htmlForm, $method], $args);
    }

    /**
	 * Renders form to string.
	 * @param can throw exceptions? (hidden parameter)
	 */
	// public function __toString(): string
	// {
    //     try {
    //         $this->htmlForm->construct = true;
    //         return $this->htmlForm->__toString();
    //     } catch (\Throwable $e) {
    //         trigger_error('Exception in ' . __METHOD__ . "(): {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}", E_USER_ERROR);
    //     }
	// }
}