<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Validate;

use RecursiveArrayIterator;
use RecursiveIteratorIterator;

class Message
{
    protected $fields = [];
    protected $messages = [];
    
    public function addMessage($error, $key = null)
    {
        if ($key) {
            $this->messages[$key][] = $error;
            $this->addField($key);
        } else {
            $this->messages[] = $error;
        }
        
        return $this;
    }

    public function addField($field)
    {
        if (!in_array($field, $this->fields))
            $this->fields[] = $field;
        return $this;
    }

    public function all($key = null) 
    {
        if ($key) {
            return isset($this->messages[$key]) ? $this->messages[$key] : '';
        }
        return $this->flattenArray($this->messages);
    }

    protected function flattenArray(array $args)
    {
        return iterator_to_array(new RecursiveIteratorIterator(
            new RecursiveArrayIterator($args)
        ), false);
    }
    public function hasMessages()
    {
        return count($this->all()) ?:false;
    }

    public function hasErrors()
    {
        return $this->hasMessages();
    }

    public function first($key) 
    {
        return isset($this->all($key)[0]) ? $this->all($key)[0] : '';
    }

    public function has($key)
    {
        return isset($this->messages[$key])?:false;
    }

    public function getFirst()
    {
        $firstMessage = [];
        foreach ($this->fields as $field) {
            $firstMessage[] = $this->first($field);
        }
        return $firstMessage;
    }

    public function get()
    {
        return $this->getFirst();
    }
}