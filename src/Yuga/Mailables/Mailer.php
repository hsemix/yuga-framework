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
     * Make settings for the Mail 0752336859 kakembo jimmy
     * @param $args
     */
    public function setArgs(array $args = [])
    {
        $this->settings = $args;
    }
    /**
     * Set Mailer object dynamically
     * @param $mailer
     */
    public function setMailable($mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Send Message using the set variables
     * @param \Yuga\Views\View $template
     * @param \array $data
     * @param \Closure $callback
     */
    public function send($template, array $data = [], ?Closure $callback = null)
    {
        $message = new Message($this->mailer);

        if (isset($this->settings['from'])) {
            $message->from($this->settings['from']);
        }

        if ($callback instanceof \Closure) {
            call_user_func($callback, $message);
        }

        $this->mailer->send($template, $data);
    }
}