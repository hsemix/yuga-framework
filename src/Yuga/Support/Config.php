<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Support;

class Config
{
    protected $data;
    protected $default = null;
    
    public function load($file, $key = null)
    {
        $file = str_replace(".", "/", $file);
        $file = require path($file.'.php');

        if ($key)
            $this->data[$key] = $file;
        else
            $this->data = $file;
        
        return $this;
    }

    public function get($key, $default = null)
    {
        $this->default = $default;
        $segments = explode('.', $key);
        $data = $this->data;
        foreach ($segments as $segment) {
            if (isset($data[$segment])) {
                $data = $data[$segment];
            } else {
                $data = $this->default;
                break;
            }
        }

        return $data;
    }

    public function exists($key)
    {
        return $this->get($key) !== $this->default;
    }

    public function getAll()
    {
        return $this->data;
    }
}