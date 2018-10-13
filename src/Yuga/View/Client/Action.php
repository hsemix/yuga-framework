<?php
namespace Yuga\View\Client;
/**
 * Class Action
 *
 * Abstract class for any parameter of any action
 *
 * @author Hamidouh Semix <semix.hamidouh@gmail.com>
 * @access   public
 * @package  jQuery
 */
class Action
{
    /**
     * add param to list
     * 
     * @param  string $param
     * @param  string $value
     * @return static
     */
    public function add($param, $value)
    {
        $this->$param = $value;
        return $this;
    }
}