<?php
namespace Yuga\Views\Widgets\Html;

class HtmlSubmitButton extends HtmlButton
{

    public $onClick = [];

    public function __construct($type = 'submit', $name, $value = null)
    {
        parent::__construct($type, $name, $value);
        $this->type($type);
        $this->name($name);

        if ($value !== null) {
            $this->value($value);
        }
        $this->setClosingType(self::CLOSE_TYPE_SHORT);
    }

    public function __toString()
    {
        try {
            if (request()->getMethod() == 'post') {
                // do onClick events and onSubmit events
                $this->clickedButton($this);
            }
            return parent::__toString();
        } catch (\Throwable $e) {
            trigger_error('Exception in ' . __METHOD__ . "(): {$e->getMessage()} in {$e->getFile()}: {$e->getLine()}", E_USER_ERROR);
        }
    }

    protected function clickedButton($html)
    {
        if ($this->isClicked()) {
            if (count($this->onClick) > 0) {
                foreach ($this->onClick as $clickHandlers)
                    $clickHandlers($html, request()->except([$this->getAttribute('name')[0], '_token']));
            }
        }
        return;
    }

    public function addOnClick($handler)
    {
        $this->onClick[] = $handler;

        return $this;
    }
    
}