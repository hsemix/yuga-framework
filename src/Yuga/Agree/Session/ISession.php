<?php
namespace Yuga\Agree\Session;

interface ISession
{
    public function check_login();
    public function isLoggedIn();
    public function is_logged_in();
    public function login($user = null);
    public function logout();
    public static function put($name, $value);
    public static function exists($name);
    public static function delete($name);
    public static function get($name);
    public static function flash($name, $string = null);
}