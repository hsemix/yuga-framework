<?php
namespace Yuga\Database;

use Yuga\Application;
use Yuga\Providers\ServiceProvider;
use Yuga\Database\Connection\Connection;
use Yuga\Database\ElegantManager\Manager;

class ElegantServiceProvider extends ServiceProvider
{
    public function load(Application $app)
    {
        if (!is_null(env('DATABASE_NAME'))) {
            $config = $app->config->load('config.Config');
            $connection = $app->singleton('connection', Connection::class);
            $app->resolve('connection', [
                $config->get('db.'.$config->get('db.defaultDriver'))
            ]);
            
            $manager = new Manager;
            $manager->addConnection($app->getBinding('connection'));
            $manager->startElegant();
        }
    }
}