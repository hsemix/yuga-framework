<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Widgets;
class NotFound extends Framework
{
    public function __construct()
    {
        parent::__construct();
        $this->applicationMenu->addClass('hidden');
        $this->getSite()->setTitle('Page not found');
    }
}