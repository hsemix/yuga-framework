<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Widgets;
class Welcome extends Framework
{
    protected $appName;
    public function __construct($appName)
    {
        parent::__construct();
        $this->appName = $appName;
    }
    public function getAppName()
    {
        return $this->appName;
    }
}