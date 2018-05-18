<?php
namespace Yuga\Views\Widgets\Html;

use Yuga\Views\Widgets\Xml\XmlText;
use Yuga\Views\Widgets\Html\Support\IHtmlNode;
class HtmlText extends XmlText implements IHtmlNode
{

    public function toHtml()
    {
        return $this->toXml();
    }

}