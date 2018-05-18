<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Models;

use Yuga\Route\Route;
use Yuga\Authenticate\Authenticate;

class Auth
{
  /**
   * Determine whether or not the auth routes have been registered.
   * @var bool
   */
  protected static $authRoutes = false;

  /**
   * Return all routes responsible for the authentication
   */
  public function routes()
	{
    static::$authRoutes = true;
    Route::get('/login', 'Auth\LoginController@showLoginForm')->name('login');
    Route::get('/register', 'Auth\RegisterController@showRegistrationForm')->name('register');
    Route::form('/logout', 'Auth\LoginController@logout')->name('logout');
    Route::post('/login', 'Auth\LoginController@login');
    Route::post('/register', 'Auth\RegisterController@register');
  }
  
  /**
   * Return whether or not auth routes have been register
   */
  public static function authRoutesExist()
  {
    return static::$authRoutes;
  }
  
  /**
   * Call all \Yuga\Authenticate\Authenticate methods statically through \Auth
   * 
   * @param string $method
   * @param string $args
   * 
   * @return (new \Yuga\Authenticate\Authenticate)->$method($args)
   */
  public static function __callStatic($method, $args) 
  {
    return call_user_func_array([new Authenticate, $method], $args);
  }

  /**
   * Call all \Yuga\Authenticate\Authenticate methods through \Auth
   * 
   * @param string $method
   * @param any $args
   * 
   * @return (new \Yuga\Authenticate\Authenticate)->$method($args)
   */
  public function __call($method, $args) 
  {
    return call_user_func_array([new Authenticate, $method], $args);
  }
}