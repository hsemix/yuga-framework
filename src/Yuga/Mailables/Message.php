<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Mailables;

class Message
{
    /**
     * @param $mailer
     */
    public function __construct(protected $mailer)
    {
    }

    /**
     * @param string $address
     * @param string|null $name
     */
    public function to($address, $name = null)
    {
        $this->mailer->addToRecipient($address, $name);
    }

    /**
     * @param string $subject
     */
    public function subject($subject)
    {
        $this->mailer->setSubject($subject);
    }

    /**
     * @param string $body
     */
    public function body($body)
    {
        $this->mailer->setHtmlBody($body);
    }


    /**
     * @param string $email
     * @param string|null $name
     */
    public function replyTo($email, $name = null)
    {
        $this->mailer->addReplyTo($email, $name);
    }

    /**
     * @param string $email
     * @param string|null $name
     */
    public function from($email, $name = null)
    {
        $this->mailer->setFromAddress($email, $name);
    }

    /**
     * @param string $email
     * @param string|null $name
     */
    public function reciever($email, $name = null)
    {
        $this->mailer->addToRecipients($email, $name);
    }

    /**
     * @param string $email
     * @param string|null $name
     */
    public function cc($email, $name = null)
    {
        $this->mailer->addCC($email, $name);
    }

    /**
     * @param string $email
     * @param string|null $name
     */
    public function bcc($email, $name = null)
    {
        $this->mailer->addBCC($email, $name);
    }

    /**
     * @param string $location
     * @param string|null $filename
     */
    public function attach($location, $filename = null)
    {
        $this->mailer->addAttachment($location, '', $filename);
    }
}