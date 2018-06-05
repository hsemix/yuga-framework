<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Providers;

use Yuga\Interfaces\Application\Application;

class ClassAliasServiceProvider extends ServiceProvider
{
    public function load(Application $app)
    {
        $config = $app->config->load('config.ClassAlias');

        foreach ($config->getAll() as $alias => $class) {
            class_alias($class, $alias);
        }
    }
}