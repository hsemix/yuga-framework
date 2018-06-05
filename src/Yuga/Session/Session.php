<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Session;

use Yuga\Guid;
use Yuga\Database\Elegant\Model;
use Yuga\Application\Application;
use Yuga\Interfaces\Session\ISession;

class Session implements ISession
{
	private $logged_in = FALSE;
	public $user_id;
	private static $instances = [];
	private $app;
	private $sessionName;
	public function __construct(Application $app)
	{
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}
		$this->app = $app;
		$config = $this->app->config->load('config.Settings');
		$this->sessionName = $config->get('session.name');
		$this->check_login();
	}

	public function getName()
	{
		return $this->sessionName;
	}
	public function is_logged_in()
	{
		if (self::exists($this->sessionName) && self::get($this->sessionName)) {
			$this->logged_in = TRUE;
		}
		return $this->logged_in;
	}

	public function isLoggedIn()
	{
		return $this->is_logged_in();
	}
	
	public function login($user = null) 
	{
		
		if ($user && $user instanceof Model) {
			self::put($this->sessionName, $user->{$user->getPrimaryKey()});
			$this->user_id = self::get($this->sessionName);
			$this->logged_in = true;
		}
	}

	public function getUserId()
	{
		return $this->get($this->sessionName);
	}
	
	public function logout()
	{
		self::delete($this->sessionName);
		$this->logged_in = false;
	}

	public function check_login()
	{
		if (self::exists($this->sessionName)) {
			$this->user_id = self::get($this->sessionName);
			$this->logged_in = true;
		} else {
			$this->logged_in = false;
		}
	}

	public static function put($name, $value)
	{
		$data = [serialize($value), static::getSecret()];
        $data = Guid::encrypt(static::getSecret(), implode('|', $data));
        return $_SESSION[$name] = $data;
	}
	public static function exists($name)
	{
		return (isset($_SESSION[$name])) ? true : false;
	}

	public static function delete($name)
	{
		if (self::exists($name)) {
			unset($_SESSION[$name]);
			return true;
		}

		return false;
	}

	public static function deleteMany($names)
	{
		foreach ($names as $name) {
			self::delete($name);
		}
	}

	public static function getSecret()
    {
        return md5(env('APP_SECRET', 'NoApplicationSecretDefined'));
    }

	public static function get($name, $defaultValue = null)
	{
		if (static::exists($name)) {
            $value = $_SESSION[$name];
            if (trim($value) !== '') {
                $value = Guid::decrypt(static::getSecret(), $value);
                $data = explode('|', $value);
                if (is_array($data) && trim(end($data)) === static::getSecret()) {
                    return unserialize($data[0]);
                }
            }
        }

        return $defaultValue;
	}
	public static function flash($name, $string = null)
	{
		if (self::exists($name)) {
			$session = self::get($name);
			self::delete($name);
			return $session;
		} else {
			if ($string)
				self::put($name, $string);
		}
	}
}