<?php
namespace Yuga\View\Client;
/**
 * Element - class for work with jQuery framework
 *
 * @author Hamidouh Semix
 * @access   public
 * @package  jQuery
 */
class Element
{
    /**
     * selector path
     * @var string
     */
    public $selector;
    
    /**
     * methods
     * @var array
     */
    public $method = [];
    
    /**
     * args
     * @var array
     */
    public $params = [];
    
    /**
     * __construct
     * contructor of jQuery
     *
     * 
     */
    public function __construct($selector)
    {
        Jquery::addElement($this); 
        $this->selector = $selector;
    }
    
    /**
     * __call
     *
     * @return static
     */
    public function __call($method, $args)
    {
        $this->method[] = $method;
        $this->params[] = $args;
        
        return $this;
    }
    
    /**
     * end
     * need to create new jQuery
     *
     * @return static
     */
    public function end()
    {
        return new static($this->selector);
    }

    public function run()
    {
        return Jquery::run();
    }
}