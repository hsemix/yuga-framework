<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Mailables\Native;

use Yuga\Mailables\Mailable;
class YugaMailerOld extends Mailable
{
    private $from;
    private $template;
    private $subject;
    private $recipients = [];
    private $recipientsMany = [];
    private $cc = [];
    private $bcc = [];
    private $replyTo;

    public function setFromAddress($mail, $name = null)
    {
        
        if ($name) {
            $this->from = $name.' <'.$mail.'>';
        } else {
            $this->from = '<'.$mail.'>';
        }
    }

    public function setHtmlBody($html)
    {
        $this->template = $html;
    }

    public function addToRecipient($mail, $name = null)
    {
        if ($name) {
            $this->recipients[] = $name.' <'.$mail.'>';
        } else {
            $this->recipients[] = '<'.$mail.'>';
        }
        return $this;
    }

    public function addCC($mail, $name = null)
    {
        if ($name) {
            $this->cc[] = $name.' <'.$mail.'>';
        } else {
            $this->cc[] = '<'.$mail.'>';
        }
        
        return $this;
    }

    public function addBCC($mail, $name = null)
    {
        if ($name) {
            $this->bcc[] = $name.' <'.$mail.'>';
        } else {
            $this->bcc[] = '<'.$mail.'>';
        }
        
        return $this;
    }

    public function addToRecipients($mail, $name = null)
    {
        if ($name) {
            $this->recipientsMany[] = $name.' <'.$mail.'>';
        } else {
            $this->recipientsMany[] = '<'.$mail.'>';
        }
        
        return $this;
    }

    public function setSubject($text)
    {
        $this->subject = $text;
    }

    public function addReplyTo($mail, $name = null)
    {
        
        if ($name) {
            $this->replyTo = $name.' <'.$mail.'>';
        } else {
            $this->replyTo = '<'.$mail.'>';
        } 
    }

    public function send($template, array $data = [])
    {
        $template = $this->view->render($template, $data);

        $to      = implode(', ', $this->recipients);
        $subject = $this->subject;
        $headers = "From: {$this->from}".PHP_EOL;
        if (count($this->recipientsMany) > 0) {
            $headers .= 'To: '. implode(', ', $this->recipientsMany).PHP_EOL;
        }

        if (count($this->cc) > 0) {
            $headers .= 'Cc: '. implode(', ', $this->cc).PHP_EOL;
        }

        if (count($this->bcc) > 0) {
            $headers .= 'Bcc: '. implode(', ', $this->bcc).PHP_EOL;
        }
        $replyTo = $this->from;
        if ($this->replyTo) {
            $replyTo = $this->replyTo;
        }
        $headers .= "Reply-To: {$replyTo}".PHP_EOL.'X-Mailer: PHP/'.phpversion();
		$headers .= PHP_EOL."MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

        return @mail($to, $subject, $template ,$headers);
    }
}