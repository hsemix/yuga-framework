<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Support;

use Yuga\Application;

interface IServiceProvider
{
    public function register(Application $app);
}