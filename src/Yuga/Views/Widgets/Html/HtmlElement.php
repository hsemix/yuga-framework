<?php
namespace Yuga\Views\Widgets\Html;

use Yuga\Views\Widgets\Xml\XmlElement;
use Yuga\Views\Widgets\Html\Support\IHtmlNode;

class HtmlElement extends XmlElement implements IHtmlNode
{

    public function isContainer()
    {
        switch (strtolower($this->getTag())) {
            case 'div':
            case 'span':
            case 'strong':
            case 'a':
            case 'b':
            case 'em':
            case 'i':
            case 'ul':
            case 'li':
            case 'ol':
            case 'dd':
            case 'dt':
            case 'dl':
            case 'table':
            case 'tr':
            case 'thead':
            case 'tbody':
            case 'tfoot':
            case 'td':
            case 'th':
            case 'title':
            case 'head':
            case 'body':
            case 'textarea':
            case 'html':
            case 'pre':
            case 'code':
            case 'h1':
            case 'h2':
            case 'h3':
            case 'h4':
            case 'h5':
            case 'p':
            case 'blink':
            case 'script':
                return true;
        }

        return false;
    }

}