<?php
/*
 *  (c) Rogério Adriano da Silva <rogerioadris.silva@gmail.com>
 */

use Symfony\Component\Console\Application;
use Crud\Command as Commands;

$console = new Application('CRUD Admin Generator command instalation', '1.0');

$console->add(new Commands\GeneratorCommand($app));
$console->add(new Commands\UserCreateCommand($app));
$console->add(new Commands\UserTableCommand($app));

return $console;
