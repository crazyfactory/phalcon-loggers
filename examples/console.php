<?php

// This is an example of how to integrate phalcon-loggers library into console app.

$di      = new Phalcon\Di\FactoryDefault\Cli;
$console = new Phalcon\Cli\Console;
$console->setDI($di);

// To see different usages of Service::register() or custom injection to DI please refer to 'default.php' and 'micro.php'.
(new CrazyFactory\PhalconLogger\Service)->register(null, $di);

$arguments = [
    'task' => $argv[1] ?? 'main',
    'action' => $argv[2] ?? 'main',
    'params' => array_slice($argv, 3),
];

try {
    $console->handle($arguments);
    exit(0);
} catch (\Throwable $e) {
    $di->getShared('logger')->logException($e);
    exit(255);
}
