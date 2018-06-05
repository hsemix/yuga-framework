<?php
namespace Yuga\Views\Widgets\Html;

class Html
{
    const CLOSE_TYPE_TAG = 'tag';
    const CLOSE_TYPE_NONE = 'none';
    const CLOSE_TYPE_SHORT = 'short';
    protected $tag;
    protected $innerHtml = [];
    protected $closingType;
    protected $prepended = [];
    protected $attributes = [];
    protected $afterHtml = [];
    protected $beforeHtml = [];
    

    public function __construct($tag)
    {
        $this->tag = $tag;
        $this->closingType = static::CLOSE_TYPE_TAG;
    }

    /**
     * @param array $html
     * @return static
     */
    public function setInnerHtml(array $html)
    {
        $this->innerHtml = $html;

        return $this;
    }

    public function addInnerHtml($html)
    {
        $this->innerHtml[] = $html;

        return $this;
    }

    public function append($html) 
    {
        return $this->addInnerHtml($html);
    }

    /**
     * Replace attribute
     *
     * @param string $name
     * @param string $value
     * @return static
     */
    public function replaceAttribute($name, $value = '')
    {
        $this->attributes[$name] = array($value);

        return $this;
    }

    /**
     * Adds new attribute to the element.
     *
     * @param string $name
     * @param string $value
     * @return static
     */
    public function addAttribute($name, $value = '')
    {
        if (isset($this->attributes[$name]) && in_array($value, $this->attributes[$name], true) === false) {
            $this->attributes[$name][] = $value;
        } else {
            $this->attributes[$name] = [$value];
        }

        return $this;
    }

    /**
     * @param array $attributes
     * @return static $this
     */
    public function setAttributes(array $attributes)
    {
        foreach ($attributes as $name => $value) {
            $this->addAttribute($name, $value);
        }

        return $this;
    }

    public function attr($name, $value = '', $replace = true)
    {
        if ($replace === true) {
            return $this->replaceAttribute($name, $value);
        }

        return $this->addAttribute($name, $value);
    }

    public function id($id)
    {
        return $this->addAttribute('id', $id);
    }

    public function style($css)
    {
        return $this->addAttribute('style', $css);
    }

    protected function render()
    {
        $output = '';
        foreach ($this->beforeHtml as $before) {
            $html = $before;
            $output .= ($html instanceof static) ? $html->render() : $html;
        }
        $output .= '<' . $this->tag;

        foreach ($this->attributes as $key => $val) {
            $output .= ' ' . $key;
            if ($val[0] !== null || strtolower($key) === 'value') {
                $val = htmlentities(implode(' ', $val), ENT_QUOTES, app()->getCharset());
                $output .= '="' . $val . '"';
            }
        }

        if ($this->closingType === static::CLOSE_TYPE_SHORT) {
            $output .= ' />';
        } else {
            $output .= '>';
        }
        $prependedItems = $this->prepended;

        krsort($prependedItems);

        foreach ($prependedItems as $prepended) {
            $html = $prepended;
            $output .= ($html instanceof static) ? $html->render() : $html;
        }
        

        for($i = 0, $max = count($this->innerHtml); $i < $max; $i++) {
            $html = $this->innerHtml[$i];
            $output .= ($html instanceof static) ? $html->render() : $html;
        }

        if($this->closingType === static::CLOSE_TYPE_TAG) {
            $output .= '</' . $this->tag . ">";
        }

        foreach ($this->afterHtml as $after) {
            $html = $after;
            $output .= ($html instanceof static) ? $html->render() : $html;
        }
        return $output;
    }

    public function prepend($html)
    {
        $this->prepended[] = $html;
        return $this;
    }

    /**
     * Add class
     * @param string $class
     * @return static
     */
    public function addClass($class)
    {
        return $this->addAttribute('class', $class, false);
    }

    /**
     * @return string $closingType
     */
    public function getClosingType()
    {
        return $this->closingType;
    }

    /**
     * @param string $closingType
     * @return static $this;
     */
    public function setClosingType($closingType)
    {
        $this->closingType = $closingType;

        return $this;
    }

    public function __toString()
    {
        return $this->render();
    }

    public function getInnerHtml()
    {
        return $this->innerHtml;
    }

    

    public function getAttribute($name)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    public function getTag()
    {
        return $this->tag;
    }

    public function removeAttribute($name)
    {
        if (isset($this->attributes[$name])) {
            unset($this->attributes[$name]);
        }

        return $this;
    }

    public function after($html)
    {
        $this->afterHtml[] = $html;

        return $this;
    }

    public function before($html)
    {
        $this->beforeHtml[] = $html;

        return $this;
    }
}