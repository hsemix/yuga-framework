<?php
/**
 * @author Mahad Tech Solutions
 */

namespace Yuga\Mailables;

use Closure;

class Mailer
{
    protected $mailer;
    private $settings = [];

    /**
     * Make settings for the Mail 0752336859 kakembo jimmy.
     *
     * @param $args
     *
     * @return null
     */
    public function setArgs(array $args = [])
    {
        $this->settings = $args;
    }

    /**
     * Set Mailer object dynamically.
     *
     * @param $mailer
     *
     * @return null
     */
    public function setMailable($mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Send Message using the set variables.
     *
     * @param \Yuga\Views\View $template
     * @param \array           $data
     * @param \Closure         $callback
     *
     * @return null
     */
    public function send($template, array $data = null, Closure $callback)
    {
        $message = new Message($this->mailer);

        if (isset($this->settings['from'])) {
            $message->from($this->settings['from']);
        }

        call_user_func($callback, $message);

        $this->mailer->send($template, $data);
    }
}
