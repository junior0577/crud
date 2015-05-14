<?php
/*
 *  (c) Rogério Adriano da Silva <rogerioadris.silva@gmail.com>
 */

$app = require_once __DIR__.'/bootstrap.php';

$app->mount('/', require_once __DIR__.'/routes.php');

return $app;
