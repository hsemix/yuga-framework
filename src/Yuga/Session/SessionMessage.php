<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Session;

use Yuga\Views\Widgets\Form\FormMessage;

class SessionMessage
{
    const KEY = 'MSG';

    protected $messages;

    public function __construct()
    {
        $this->parse();
    }

    protected function parse()
    {
        $this->messages = Session::get(self::KEY);
    }

    public function save()
    {
        Session::put(self::KEY, $this->messages);
    }

    public function set(FormMessage $message, $type = null)
    {
        // Ensure no double posting
        if (isset($this->messages[$type]) && is_array($this->messages[$type])) {
            if (!in_array($message, $this->messages[$type])) {
                $this->messages[$type][] = $message;
                $this->save();
            }
        } else {
            $this->messages[$type][] = $message;
            $this->save();
        }
    }

    /**
     * Get messages
     * @param string|null $type
     * @param mixed|null $defaultValue
     * @return \Pecee\UI\Form\FormMessage|array
     */
    public function get($type = null, $defaultValue = null)
    {
        if ($type !== null) {
            return isset($this->messages[$type]) ? $this->messages[$type] : $defaultValue;
        }

        return $this->messages;
    }

    /**
     * Checks if there's any messages
     * @param string|null $type
     * @return boolean
     */
    public function has($type = null)
    {
        if ($type !== null) {
            return (isset($this->messages[$type]) && count($this->messages[$type]) > 0);
        }

        return (count($this->messages) > 0);
    }

    public function clear($type = null)
    {
        if ($type !== null) {
            unset($this->messages[$type]);
            $this->save();
        } else {
            Session::delete(self::KEY);
        }
    }
}