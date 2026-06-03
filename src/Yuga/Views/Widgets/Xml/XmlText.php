<?php

declare(strict_types=1);

namespace Yuga\Views\Widgets\Xml;
use Yuga\Views\Widgets\Xml\Support\IXmlNode;

class XmlText implements IXmlNode
{

    protected $parent;

    public function __construct(protected $text)
    {
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    public function getText()
    {
        return $this->text;
    }

    public function setText($text)
    {
        $this->text = $text;
    }

    public function __toString(): string
    {
        return (string) $this->text;
    }

    public function toXml()
    {
        return $this->text;
    }

}
