<?php
/*
 *  (c) Rogério Adriano da Silva <rogerioadris.silva@gmail.com>
 */

namespace Crud\Provider;

use Silex\Application;
use Silex\Provider\DoctrineServiceProvider as BaseDoctrineServiceProvider;

/**
 * Class DoctrineServiceProvider
 *
 * http://silex.sensiolabs.org/doc/providers/doctrine.html
 */
class DoctrineServiceProvider extends BaseDoctrineServiceProvider
{
    /**
     * @param Application $app
     */
    public function register(Application $app)
    {
        $app['dbs.options'] = array(
            'db' => array(
                'driver'   => 'pdo_mysql',
                'dbname'   => 'bevicred',
                'host'     => '127.0.0.1',
                'user'     => 'root',
                'password' => 'r0g3r10',
                'charset'  => 'utf8',
            ),
        );

        //
        parent::register($app);
    }
}
