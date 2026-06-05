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
            if (class_exists($alias)) {
                continue;
            }
            class_alias($class, $alias);
        }
    }
}