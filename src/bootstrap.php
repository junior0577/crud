<?php
/*
 *  (c) Rogério Adriano da Silva <rogerioadris.silva@gmail.com>
 */

use Silex\Application;

$app = new Application();

/**
 * Configurações
 */
// Url painel
$app['security_path'] = '/security';

// Prefixo url
$app['asset_path'] = '/';

// Habilitar modo desenvolvedor
$app['debug'] = true;

// http://silex.sensiolabs.org/doc/providers/session.html
$app->register(new Crud\Provider\SessionServiceProvider());

// http://silex.sensiolabs.org/doc/providers/form.html
$app->register(new Crud\Provider\FormServiceProvider());

// http://silex.sensiolabs.org/doc/providers/translation.html
$app->register(new Crud\Provider\TranslationServiceProvider());

// http://silex.sensiolabs.org/doc/providers/validator.html
$app->register(new Crud\Provider\ValidatorServiceProvider());

// http://silex.sensiolabs.org/doc/providers/url_generator.html
$app->register(new Crud\Provider\UrlGeneratorServiceProvider());

// http://silex.sensiolabs.org/doc/providers/doctrine.html
$app->register(new Crud\Provider\DoctrineServiceProvider());

// http://silex.sensiolabs.org/doc/providers/swiftmailer.html
$app->register(new Crud\Provider\SwiftmailerServiceProvider());

// http://silex.sensiolabs.org/doc/providers/service_controller.html
$app->register(new Crud\Provider\ServiceControllerServiceProvider());

// ExceptionServiceProvider
$app->register(new Crud\Provider\ExceptionServiceProvider());

// http://silex.sensiolabs.org/doc/providers/twig.html
$app->register(new Crud\Provider\TwigServiceProvider());

// http://silex.sensiolabs.org/doc/providers/monolog.html
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.sprintf('/../var/logs/%s.log', (new \DateTime())->format('Y-m-d')),
));

if ($app['debug']) {
    // https://github.com/silexphp/Silex-WebProfiler
    $app->register(new Silex\Provider\WebProfilerServiceProvider(), array(
        'profiler.cache_dir' => __DIR__.'/../var/cache',
    ));
}

// http://twig.sensiolabs.org/doc/advanced.html#creating-an-extension
$app['twig']->addExtension(new Crud\Twig\AssetTwigFunction($app));
$app['twig']->addExtension(new Crud\Twig\CamelizeTwigFunction($app));

// http://silex.sensiolabs.org/doc/providers/security.html
$app->register(new Crud\Provider\SecurityServiceProvider());

return $app;
