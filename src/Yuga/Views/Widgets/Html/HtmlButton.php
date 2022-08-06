<?php

namespace Yuga\Views\Widgets\Html;

class HtmlButton extends Html
{
    public function __construct($type = 'button', $name, $value = null)
    {
        parent::__construct('input');

        $this->type($type);
        $this->name($name);

        if ($value !== null) {
            $this->value($value);
        }
        $this->setClosingType(self::CLOSE_TYPE_TAG);
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

    public function disabled()
    {
        return $this->addInputAttribute('disabled');
    }

    public function size($size)
    {
        return $this->addAttribute('size', $size);
    }

    public function type($type)
    {
        return $this->addAttribute('type', $type);
    }

    public function addInputAttribute($name)
    {
        $this->addAttribute($name, null);

        return $this;
    }
    /**
	 * Button clicked
	 */
	public function isClicked()
	{
        $value = $this->getValue();
		return $value !== null && $value !== [];
    }
    
    public function getValue()
    {
        $value = $this->getAttribute('value');
        return  $value ? $value[0] : null;
    }

    public function getName()
    {
        $name = $this->getAttribute('name');
        return  $name ? $name[0] : null;
    }
}