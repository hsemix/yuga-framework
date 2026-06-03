<?php

declare(strict_types=1);

namespace Yuga\Views\Widgets\Html;

class HtmlCheckbox extends HtmlInput
{

    public function __construct($name, $value = null)
    {
        parent::__construct('checkbox', $name, $value ?? 1);
    }

}
