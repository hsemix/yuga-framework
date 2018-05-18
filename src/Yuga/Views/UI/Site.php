<?php
namespace Yuga\Views\UI;

use Yuga\Views\Widgets\Html\Html;

class Site
{
    const SECTION_DEFAULT = 'default';
    protected $title;
    protected $js = [];
    protected $css = [];
    protected $header = [];
    protected $keywords = [];
    protected $description;

    public function getTitle()
    {
        return $this->title;
    }

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    

    public function addCss($path, $section = self::SECTION_DEFAULT)
    {
        if (isset($this->css[$section]) === false || in_array($path, $this->css[$section], true) === false) {
            $this->css[$section][] = $path;
        }

        return $this;
    }

    public function addJs($path, $section = self::SECTION_DEFAULT)
    {
        if (isset($this->js[$section]) === false || in_array($path, $this->js[$section], true) === false) {
            $this->js[$section][] = $path;
        }

        return $this;
    }

    public function clearCss()
    {
        $this->cssFilesWrapped = [];

        return $this;
    }

    public function clearJs()
    {
        $this->jsFilesWrapped = [];

        return $this;
    }

    public function setKeywords(array $keywords)
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function getKeywords()
    {
        return $this->keywords;
    }

    public function addMeta(array $attributes)
    {
        return $this->addHeader((new Html('meta'))->setAttributes($attributes));
    }

    public function addHeader(Html $el)
    {
        $this->header[] = $el;

        return $el;
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function getJs($section = self::SECTION_DEFAULT)
    {
        return isset($this->js[$section]) ? $this->js[$section] : [];
    }

    public function getCss($section = self::SECTION_DEFAULT)
    {
        return isset($this->css[$section]) ? $this->css[$section] : [];
    }

    public function getDocType()
    {
        return '<!DOCTYPE html>';
    }

}