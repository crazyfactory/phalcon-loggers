<?php

// This is an example of how to integrate phalcon-loggers library into different types of mvc apps.

$id = (new Phalcon\Security\Random)->uuid();
$di = new Phalcon\Di\FactoryDefault;

// To see different usages of Service::register() or custom injection to DI please refer to 'default.php' and 'micro.php'.
(new CrazyFactory\PhalconLogger\Service)->register(null, $di);
$di->getShared('logger')->setRequestId($id);

// MVC application of different flavors can be wrapped like so in try-catch to automatically handle errors/throw-ups.
$app = new Phalcon\Mvc\Application($di);
try {
    echo $app->handle()->getContent();
    // Some of the samples in https://github.com/phalcon/mvc/ use main() as entry point, in such case just do:
    // $app->main();
} catch (\Throwable $e) {
    $di->getShared('logger')->logException($e);

    echo 'Something is wrong, your request ID is ', $id;
}
