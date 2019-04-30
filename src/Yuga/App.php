<?php
namespace Yuga;

use Yuga\Application\Application;

class App
{
  /**
   * Call all non static methods in \Yuga\Application\Application statically as if they were defined as static
   */
  public static function __callStatic($method, $args)
  {
    return call_user_func_array([Application::getInstance(), $method], $args);
  }
  /**
   * Interface all methods of \Yuga\Application\Application to be called by this class
   */
  public function __call($method, $args) 
  {
    return call_user_func_array([Application::getInstance(), $method], $args);
  }
}