<?php

declare(strict_types=1);

namespace Yuga\Views\Widgets\Html;

use Yuga\Views\Widgets\Xml\XmlElement;
use Yuga\Views\Widgets\Html\Support\IHtmlNode;

class HtmlElement extends XmlElement implements IHtmlNode
{

    public function isContainer()
    {
        return match (strtolower((string) $this->getTag())) {
            'div', 'span', 'strong', 'a', 'b', 'em', 'i', 'ul', 'li', 'ol', 'dd', 'dt', 'dl', 'table', 'tr', 'thead', 'tbody', 'tfoot', 'td', 'th', 'title', 'head', 'body', 'textarea', 'html', 'pre', 'code', 'h1', 'h2', 'h3', 'h4', 'h5', 'p', 'blink', 'script' => true,
            default => false,
        };
    }

}
