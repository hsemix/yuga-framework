<?php
namespace Yuga\Hash;

use Yuga\Database\Elegant\Model;

class Hash
{
    
    private $crypt;
    private $algorithm = 'sha256';
    protected static $instance;

    public function __construct($cryptType = null)
    {
        if ($cryptType) {
            $this->setCrypt($cryptType);
        } else {
            $this->setCrypt(env('AUTH_MODEL_CRYPT_TYPE', 'crypt'));
        }
    }

    public function make($string, $salt = '')
    {
        if ($salt == '' || is_null($salt)) 
            $salt = env('APP_SECRET', 'NoApplicationSecret');
    
        if ($this->algorithm == 'crypt')
            return crypt($string, $salt);

        return hash($this->getAlgorithm(), $string . $salt);
    }

    public function setAlgorithm($algorithm = 'crypt')
    {
        $this->algorithm = $algorithm;
        return $this;
    }

    public static function salt($length = 8)
    {
        if (function_exists('mcrypt_create_iv')) {
            $salt = mcrypt_create_iv($length);
        } else {
            $salt = random_bytes($length);
        }
        return substr(bin2hex($salt), 0, $length);
    }

    public function unique()
    {
        return $this->make(uniqid());
    }

    public function code($length = 8)
    {
        return "$1$" . $this->salt($length) . "$";
    }

    public function password($string, $code = null)
    {
        return $this->make($string, $code);
    }

    public function getCrypt()
    {
        return $this->crypt;
    }

    public function setCrypt($crypt = 'crypt')
    {
        return $this->setAlgorithm($crypt);
    }

    public function getAlgorithm()
    {
        return $this->algorithm;
    }

    public function getSalt(Model $model)
    {
        $modelUserSalt = env('AUTH_MODEL_TOKEN_FIELD');
        $appSecret = env('APP_SECRET', 'NoApplicationSecret');
        if (is_null($modelUserSalt)) {
            $modelUserSalt = $appSecret;
        } else {
            $modelUserSalt = $model->$modelUserSalt?:$appSecret;
        }
        return $modelUserSalt;
    }
}