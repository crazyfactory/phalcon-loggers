<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

(new Phalcon\Loader)->registerNamespaces(['CrazyFactory\\PhalconLogger' => __DIR__ . '/../src/'])->register();

putenv('APP_NAME=phalcon-loggers');
putenv('APP_ENV=staging');
putenv('SLACK_CURL_METHOD=exec');
putenv('SENTRY_CURL_METHOD=exec');
//
// Uncomment below lines and populate the values for a test.
// Or you can use `export` in the CLI and run `php phalcon-loggers/examples/default.php`
//
// putenv('SLACK_WEBHOOK_URL=');
// putenv('SENTRY_KEY=');
// putenv('SENTRY_SECRET=');
// putenv('SENTRY_PROJECT_ID=');
//
// require_once __DIR__ . '/../vendor/sentry/sentry/lib/Raven/Autoloader.php';
// Raven_Autoloader::register();

$id = (new Phalcon\Security\Random)->uuid();
$di = new Phalcon\Di\FactoryDefault;

(new CrazyFactory\PhalconLogger\Service)->register(null, $di);

$logger = $di->getShared('logger')->setRequestId($id);

$logger->special('slack:: test package phalcon-loggers ' . $id, ['mentions' => 'slackbot']);
$logger->critical('sentry:: test package phalcon-loggers ' . $id);

echo $id;
