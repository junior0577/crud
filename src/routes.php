<?php
/*
 *  (c) Rogério Adriano da Silva <rogerioadris.silva@gmail.com>
 */

$route = $app['controllers_factory'];

$route->get(sprintf('/%s', $app['security_path']), 'Security::index'); // Page Login
$route->get(sprintf('/%s/login', $app['security_path']), 'Security::login'); // Page Login

return $route;
