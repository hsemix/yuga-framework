<?php
namespace Yuga\Database\Query;

class DB
{
  /**
   * Simulate the QueryBuilder and represent it as DB::anyMethod()
   */
  public static function __callStatic($method, $args) 
  {
		$instance = new Builder;
		return call_user_func_array([$instance, $method], $args);
  }
  
  /**
   * Simulate the QueryBuilder and represent it as (new DB)->anyMethod()
   */
  public function __call($method, $args) 
  {
		$instance = new Builder;
		return call_user_func_array([$instance, $method], $args);
	}
}