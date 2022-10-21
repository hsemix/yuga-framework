<?php

namespace Yuga\Database;

use Yuga\Database\Connection\Connection;
use Yuga\Database\ElegantManager\Manager;
use Yuga\Interfaces\Application\Application;
use Yuga\Providers\ServiceProvider;

class ElegantServiceProvider extends ServiceProvider
{
    /**
     * Start the Elegant ORM and Connections from here.
     *
     * @param Application $app
     *
     * @return void
     */
    public function load(Application $app)
    {
        if (!is_null(env('DATABASE_NAME'))) {
            $config = $app->config->load('config.Config');
            $connection = $app->singleton('connection', Connection::class);

            $app->resolve('connection', [
                $config->get('db.'.$config->get('db.defaultDriver')),
            ]);

            $manager = new Manager();
            $manager->addConnection($app->getBinding('connection'));
            $manager->startElegant();
        }
    }
}
