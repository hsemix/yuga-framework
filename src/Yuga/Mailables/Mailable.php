<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Mailables;
use Yuga\Shared\Controller;
class Mailable
{
    use Controller;

    public function __construct()
    {
        $this->init();
    }
}