<?php
namespace Yuga\Views\Widgets\Html;

class HtmlCheckbox extends HtmlInput
{

    public function __construct($name, $value = null)
    {
        parent::__construct($name, 'checkbox', ($value === null) ? 1 : $value);
    }

}