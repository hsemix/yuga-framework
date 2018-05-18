<?php
namespace Yuga\Views\Widgets\Menu;

class MenuItem
{
    protected $name;
    protected $url;
    protected $attributes = [];
    protected $linkAttributes = [];
    protected $linkIcon;
    protected $menu;
    protected $innerContent;
    protected $parent;
    protected $isHtml = false;

    public function __construct($name, $url, $html = false)
    {
        $this->setName($name);
        $this->setUrl($url);
        $this->setReturnHtml($html);
    }

    /**
     * Add menu
     * @param \Yuga\Views\Widgets\Menu\Menu $menu
     * @return static
     */
    public function addMenu(Menu $menu)
    {
        $menu->setParent($this);
        $this->menu = $menu;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    public function setInnerContent($html)
    {
        $this->innerContent = $html;

        return $this;
    }

    public function setLinkIcon($html) 
    {
        $this->linkIcon = $html;
        return $this;
    }

    public function getLinkIcon()
    {
        return $this->linkIcon;
    }

    public function getInnerContent()
    {
        return $this->innerContent;
    }

    /**
     * @return \Yuga\Views\Widgets\Menu\Menu|null
     */
    public function getMenu()
    {
        return $this->menu;
    }

    /**
     * Adds attribute to item.
     *
     * @param string $name
     * @param string $value
     * @param bool $replace
     * @return static
     */
    public function addAttribute($name, $value, $replace = false)
    {
        if ($replace === false && isset($this->attributes[$name])) {
            $this->attributes[$name][] = $value;
        } else {
            $this->attributes[$name] = [$value];
        }

        return $this;
    }

    public function removeAttribute($name)
    {
        if (isset($this->attributes[$name])) {
            unset($this->attributes[$name]);
        }

        return $this;
    }

    /**
     * Get attributes
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Get link attributes
     * @return array
     */
    public function getLinkAttributes()
    {
        return $this->linkAttributes;
    }

    /**
     * Adds attribute to item.
     *
     * @param string $name
     * @param string $value
     * @return static
     */
    public function addLinkAttribute($name, $value)
    {
        if (isset($this->linkAttributes[$name])) {
            $this->linkAttributes[$name][] = $value;
        } else {
            $this->linkAttributes[$name] = [$value];
        }

        return $this;
    }

    public function removeLinkAttribute($name)
    {
        if (isset($this->linkAttributes[$name])) {
            unset($this->linkAttributes[$name]);
        }

        return $this;
    }

    /**
     * Get parent menu
     * @return Menu
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set parent menu
     * @param Menu $menu
     * @return static
     */
    public function setParent(Menu $menu)
    {
        $this->parent = $menu;

        return $this;
    }

    /**
     * Add class to item
     * @param string $name
     * @return static
     */
    public function addClass($name)
    {
        $this->addAttribute('class', $name);

        return $this;
    }

    public function setReturnHtml($html = false)
    {
        $this->isHtml = $html;
        return $this;
    }

    public function getReturnHtml()
    {
        return $this->isHtml;
    }

}