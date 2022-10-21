<?php

namespace Yuga\Views\Widgets\Html;

class HtmlInput extends Html
{
    public function __construct($type, $name, $value = null)
    {
        parent::__construct('input');

        $this->type($type);
        $this->name($name);

        if ($value !== null) {
            $this->value($value);
        }
        $this->setClosingType(self::CLOSE_TYPE_SHORT);
    }

    public function name($name)
    {
        return $this->addAttribute('name', $name);
    }

    public function value($value)
    {
        return $this->addAttribute('value', $value);
    }

    public function placeholder($text)
    {
        return $this->addAttribute('placeholder', $text);
    }

    public function autoComplete()
    {
        return $this->addAttribute('autocomplete', 'on');
    }

    public function readonly()
    {
        return $this->addInputAttribute('readonly');
    }

    public function disabled()
    {
        return $this->addInputAttribute('disabled');
    }

    public function autofocus()
    {
        return $this->addInputAttribute('autofocus');
    }

    public function required()
    {
        return $this->addInputAttribute('required');
    }

    public function isRequired()
    {
        return $this->required();
    }

    public function multiple()
    {
        return $this->addInputAttribute('multiple');
    }

    public function maxLength($maxLength)
    {
        return $this->addAttribute('maxlength', $maxLength);
    }

    public function size($size)
    {
        return $this->addAttribute('size', $size);
    }

    public function type($type)
    {
        return $this->addAttribute('type', $type);
    }

    public function pattern($pattern)
    {
        return $this->addAttribute('pattern', $pattern);
    }

    public function checked($checked)
    {
        if ($checked === true) {
            $this->addInputAttribute('checked');
        } else {
            $this->removeAttribute('checked');
        }
    }

    public function addInputAttribute($name)
    {
        $this->addAttribute($name, null);

        return $this;
    }

    public function withErrors()
    {
        return (new Html('div'))->addInnerHtml($this);
    }
}
