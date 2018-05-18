<?php
namespace Yuga\Views\Widgets\Xml;
use Yuga\Views\Widgets\Xml\Support\IXmlNode;

class XmlText implements IXmlNode
{

    protected $parent;
    protected $text = '';

    public function __construct($text)
    {
        $this->text = $text;
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

    public function __toString()
    {
        return $this->text;
    }

    public function toXml()
    {
        return $this->text;
    }

}