<?php
namespace Yuga\View\Client;

use Yuga\Http\Response;
use Yuga\Interfaces\Application\Application;
/**
 * Jquery
 *
 * @author Hamidouh Semix <semix.hamidouh@gmail.com>
 * @access   public
 * @package  jQuery
 * @version  0.8
 */
class Jquery
{
    /**
     * static var for realize singlton
     * @var jquery
     */
    public static $jquery;
    
    /**
     * response stack
     * @var array
     */
    public $response = [
        // actions (addMessage, addError, eval etc.)
        'action' => [],
        // jqueries
        'query' => []
    ];
    
    /**
     * init
     * init singleton if needed
     *
     * @return void
     */
    public static function getInstance()
    {
        if (empty(static::$jquery)) {
            static::$jquery = new static;
        }
        return true;
    }


    /**
     * addData
     *
     * add any data to response
     *
     * @param string $key
     * @param mixed $value
     * @param string $callBack
     * @return jQuery
     */
    public static function addData ($key, $value, $callBack = null)
    {
        static::getInstance();

        $action = new Action();
        $action->add('key', $key);
        $action->add('value', $value);
        
        // add call back func into response JSON obj
        if ($callBack) {
            $action->add("callback", $callBack);
        }

        static::addAction(__FUNCTION__, $action);

        return static::$jquery;
    }

    /**
     * addMessage
     * 
     * @param string $msg
     * @param string $callBack
     * @param array  $params
     * @return static
     */
    public static function addMessage ($msg, $callBack = null, $params = null)
    {
        static::getInstance();
        
        $action = new Action();        
        $action ->add("messgage", $msg);
        
        
        // add call back func into response JSON obj
        if ($callBack) {
            $action ->add("callback", $callBack);
        }
        
        if ($params) {
            $action ->add("params",  $params);
        }
        
        static::addAction(__FUNCTION__, $action);
        
        return static::$jquery;
    }
    
    /**
     * addError
     * 
     * @param string $msg
     * @param string $callBack
     * @param array  $params
     * @return jQuery
     */
    public static function addError ($msg, $callBack = null, $params = null)
    {
        static::getInstance();
        
        $action = new Action();        
        $action ->add("msg", $msg);

        // add call back func into response JSON obj
        if ($callBack) {
            $action->add("callback", $callBack);
        }
        
        if ($params) {
            $action->add("params",  $params);
        }
        
        static::addAction(__FUNCTION__, $action);
        
        return static::$jquery;
    }
    /**
     * evalScript
     *      
     * @param  string $foo
     * @return jQuery
     */
    public static function evalScript($foo)
    {
        static::getInstance();
        
        $action = new Action();        
        $action ->add("foo", $foo);

        static::addAction(__FUNCTION__, $action);
        
        return static::$jquery;
    }
    
    /**
     * response
     * init singleton if needed
     *
     * @return string JSON
     */
    public static function run()
    {
        static::getInstance();
        
        return response()->json(static::$jquery->response);
        // echo json_encode(static::$jquery->response);
        // exit ();
    }
    
    /**
     * addQuery
     * add query to stack
     *
     * @return Element
     */
    public static function addQuery($selector)
    {
        static::getInstance();
        
        return new Element($selector);
    }
    
    /**
     * addQuery
     * add query to stack
     * 
     * @param  Element $element
     * @return void
     */
    public static function addElement(Element &$element)
    {
        static::getInstance();
        
        static::$jquery->response['query'][] = $element;
    }
    
        
    /**
     * addAction
     * add query to stack
     * 
     * @param  string $name
     * @param  Action $action
     * @return void
     */
    public static function addAction($name, Action &$action)
    {
        static::getInstance();
        
        static::$jquery->response['action'][$name][] = $action;
    }
}